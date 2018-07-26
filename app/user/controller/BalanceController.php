<?php
/**
 * 会员中心余额控制器
 */
namespace app\user\controller;

use cmf\controller\UserBaseController;
use think\Db;
use think\Validate;
use think\Cache;
// 微信支付类
// 微信支付类
use think\WxPayApi;
use think\WxPayUnifiedOrder;
use think\JsApiPay;
use think\WxPayJsApiPay;

class BalanceController extends UserBaseController
{

  function _initialize()
  {
      parent::_initialize();
  }
  public function index()
  {
    $userid=cmf_get_current_user_id();
    $balance=Db::name('user')->where(['id'=>$userid])->field('balance')->find();
    $this->assign($balance);
    return $this->fetch();
  }
  // 充值
  public function recharge()
  {
      return $this->fetch();
  }
  // 充值 支付
  public function rechargePost()
  {
    $userid=cmf_get_current_user_id();
    $data=$this->request->param();
    if(is_numeric($data['money']) && $data['money'] > 0)
    {
      $info['userid']=$userid;
      $info['money']=$data['money'];
      $info['inputtime']=time();
      $info['orderid']='c_'.date('YmdHis').mt_rand(10000,99999);

    }else
    {
      $this->error('请输入正确金额');
    }
    /*支付*/
    require_once "../simplewind/vendor/wx_pay/lib/WxPay.Api.php";
    require_once "../simplewind/vendor/wx_pay/WxPay.JsApiPay.php";
    $input = new WxPayUnifiedOrder();
    $tools = new JsApiPay();
    // $openId = $tools->GetOpenid();

    $input->SetBody("芊芊商城-会员充值");//商品描述
    $input->SetAttach("芊芊商城");//附加数据 在查询API和支付通知中原样返回，可作为自定义参数使用
    $input->SetOut_trade_no($info['orderid']); //商户订单号
    $input->SetTotal_fee($info['money']*100);//交易总金额 。单位为分
    $input->SetTime_start(date("YmdHis"));//订单生成时间
    $input->SetTime_expire(date("YmdHis", time() + 600)); //交易失效时间 交易结束时间（非必填)
    // $input->SetGoods_tag("test"); //订单优惠标记，使用代金券或立减优惠功能时需要的参数
    $input->SetNotify_url($this->request->domain()."/".url('user/notify/recharge'));//异步接收微信支付结果通知的回调地址
    $input->SetTrade_type("JSAPI");//JSAPI 公众号支付
    // 获取openid
    $openid=Db::name('third_party_user')->where(['user_id'=>cmf_get_current_user_id()])->find();
    $input->SetOpenid($openid['openid']);//支付用户openid  非必填
    $order = WxPayApi::unifiedOrder($input);
    $jsApiParameters = $tools->GetJsApiParameters($order);
    $this->assign('jsApiParameters',$jsApiParameters);
    /*----------------*/
    return $this->fetch();
  }
  // 明细
  public function detail()
  {
    // 充值
    $userid=cmf_get_current_user_id();
    if($this->request->param('type') == 'c')
    {
      $list=Db::name('recharge')->where(['userid'=>$userid])->paginate(10);

    }elseif($this->request->param('type') == 't')
    {
      // 、提现明细
      $list=Db::name('put_card')->where(['userid'=>$userid])->paginate(10);
    }
    $this->assign("list", $list);
    $this->assign('page', $list->render());
    return $this->fetch();
  }
  // 提现至后台
  public function balancePut()
  {
    if($this->request->isPost())
    {
      $userid=cmf_get_current_user_id();
      $data=$this->request->param();
      if(is_numeric($data['money']) && $data['money'] >= 100)
      {
        // 判断余额是否充足
        $balance=Db::name('user')->where(['id'=>$userid])->field('balance')->find();
        if($balance['balance'] < $data['money'])
        {
          $this->error('您的余额不足');
        }
        $info['userid']=$userid;
        $info['money']=$data['money'];
        $info['inputtime']=time();
        $info['orderid']='t'.date('YmdHis').mt_rand(10000,99999);
        Db::name('put_forward')->insert($info);
        Db::name('user')->where(['id'=>$userid])->setDec('balance',$info['money']);
        $this->success('提现申请成功',url('user/balance/index'));
      }else
      {
        $this->error('请输入100以上金额');
      }
    }else
    {
      return $this->fetch();
    }
  }
  // 提现至微信
  public function putWechat()
  {
    require_once "../simplewind/vendor/wx_pay/lib/WxPay.Config.php";
    $url='https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    $userid=cmf_get_current_user_id();
    $rs=Db::name('third_party_user')->where(['user_id'=>$userid])->find();
    if(!$rs)
    {
      $this->error('您未绑定微信');
    }
    // 接收参数
    $data=$this->request->param();
    if(is_numeric($data['money']) && $data['money'] >= 100)
    {
      // 判断余额是否充足
      $balance=Db::name('user')->where(['id'=>$userid])->field('balance')->find();
      if($balance['balance'] < $data['money'])
      {
        $this->error('您的余额不足');
      }
      $info_insert['userid']=$userid;
      $info_insert['money']=$data['money'];
      $info_insert['inputtime']=time();
      $info_insert['orderid']='t'.date('YmdHis').mt_rand(10000,99999);

      $info['mch_appid']=\think\WxPayConfig::APPID;
      $info['mchid']=\think\WxPayConfig::MCHID;
      $info['nonce_str']=$this->getNonceStr();
      $info['partner_trade_no']=$info_insert['orderid'];//商户订单号，需保持唯一性
      $info['openid']=$rs['openid'];//openid

      $info['check_name']='NO_CHECK';//不校验真实姓名 FORCE_CHECK：强校验真实姓名
      $info['amount']=($info_insert['money'])*100;//企业付款金额，单位为分
      $info['desc']='会员提现';//企业付款操作说明信息。必填。
      $info['spbill_create_ip']=get_client_ip();//企业付款操作说明信息。必填。
      ksort($info);
      $timeStamp=$this->ToUrlParams($info).'&key='.\think\WxPayConfig::KEY;
      $sign=strtoupper(md5($timeStamp));
      $info['sign']=$sign;
      // 转为xml
      $xml=$this->ToXml($info);
      $result=$this->postData($url,$xml);
      $result = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
      if( isset($result['result_code']) && $result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS')
      {
        // 提现成功
        $info_insert['transaction_id ']=$result['payment_no'];//微信交易记录
        Db::name('put_forward')->insert($info_insert);
        Db::name('user')->where(['id'=>$userid])->setDec('balance',$info_insert['money']);
        $this->success('提现成功');
      }else
      {
        // 提现失败
        $this->error('提现失败，请联系服务商');
      }
    }else
    {
      $this->error('请输入100以上正确金额');
    }
}
  /**
	 * 格式化参数格式化成url参数
	 */
	public function ToUrlParams($data)
	{
		$buff = "";
		foreach ($data as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}

		$buff = trim($buff, "&");
		return $buff;
	}
  // 完善提现资料 银行卡
  public function perfectCard()
  {
    if($this->request->isPost())
    {
      $userid=cmf_get_current_user_id();
      $params=$this->request->param();
      $rule = [
            'money|金额' => 'require|number|between:100,10000',
            'name|姓名'     => 'require',
            'cardid|银行卡号'   => 'require|number|length:15,20',
            'cardname|开户行' => 'require',
        ];
        $validate   = Validate::make($rule);
        $result = $validate->check($params);
        if(!$result) {
            $this->error($validate->getError());
        }
        // 判断余额是否充足
        $balance=Db::name('user')->where(['id'=>$userid])->field('balance')->find();
        if($balance['balance'] < $params['money'])
        {
          $this->error('您的余额不足');
        }
        $params['userid']=$userid;
        $params['inputtime']=time();
        Db::name('put_card')->insert($params);
        Db::name('user')->where(['id'=>$userid])->setDec('balance',$params['money']);
        $this->success('申请成功!');
    }else
    {
      return $this->fetch();
    }
  }
  /**
   *
   * 产生随机字符串，不长于32位
   * @param int $length
   * @return 产生的随机字符串
   */
  public static function getNonceStr($length = 32)
  {
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {
      $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
  }
  /**
	 * 输出xml字符
	 * @throws WxPayException
	**/
	public function ToXml($arrdata)
	{
		if(!is_array($arrdata) || count($arrdata) <= 0)
	  	{
    		$this->error('数组错误');
    	}
    	$xml = "<xml>";
    	foreach ($arrdata as $key=>$val)
    	{
      		if (is_numeric($val)){
      			$xml.="<".$key.">".$val."</".$key.">";
      		}else{
      			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
      		}
      }
      $xml.="</xml>";
      return $xml;
	}
  //发送数据
public  function postData($url,$postfields){
    $ch = curl_init();
    $params[CURLOPT_URL] = $url;    //请求url地址
    $params[CURLOPT_HEADER] = false; //是否返回响应头信息
    $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
    $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
    $params[CURLOPT_POST] = true;
    $params[CURLOPT_POSTFIELDS] = $postfields;
    $params[CURLOPT_SSL_VERIFYPEER] = false;
    $params[CURLOPT_SSL_VERIFYHOST] = false;
    //以下是证书相关代码
    $params[CURLOPT_SSLCERTTYPE] = 'PEM';
    // $params[CURLOPT_SSLCERT] = '../simplewind/wendor/wx_pay/cert/apiclient_cert.pem';//绝对路径
    $params[CURLOPT_SSLCERT] = './apiclient_cert.pem';//绝对路径
    $params[CURLOPT_SSLKEYTYPE] = 'PEM';
    $params[CURLOPT_SSLKEY] = './apiclient_key.pem';//绝对路径
    curl_setopt_array($ch, $params); //传入curl参
    $content = curl_exec($ch); //执行
    curl_close($ch); //关闭连接
    return $content;
}


}
