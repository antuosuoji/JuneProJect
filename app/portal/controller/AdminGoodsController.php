<?php

namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\GoodsModel;
use app\portal\model\TypeModel;
use app\portal\model\BrandModel;
use app\portal\model\PortalCategoryModel;
use think\Db;
use think\validate;

class AdminGoodsController extends AdminBaseController {

	public function __construct(){

		parent::__construct();

		$this->goodsModel = new GoodsModel();

	}
	//展示

	public function index(){


    $where=" 1 =1";
    if($this->request->param('category') && $this->request->param('category') !=0)
    {
    $typeid=$this->request->param('category');
    $where.= " and g.category_id ='$typeid'";
    }
    if($this->request->param('keyword'))
    {
    $keyword=$this->request->param('keyword');

    $where.= " and g.gname like '%$keyword%'";
    }
        $goods = new GoodsModel();
        $goods = $goods->where($where)
                       ->alias('g')
                       ->join('portal_category t','g.category_id = t.id')
                       ->join('brand b','g.brand_id = b.id')
											 ->order('list_order_g')
                       ->paginate(10);
		$portalCategoryModel = new PortalCategoryModel();
			$categoryId 	 = $this->request->param('category', 0, 'intval');
		$categoryTree    = $portalCategoryModel->adminCategoryTree($categoryId);
		$this->assign('page',$goods->render());
		$this->assign('category_tree', $categoryTree);
		$this->assign("goods",$goods);
		return $this->fetch();

	}

	//添加
	public function add() {
    //商品分类
        $param           = $this->request->param();
        $categoryId      = $this->request->param('category', 0, 'intval');
    $portalCategoryModel = new PortalCategoryModel();
    $categoryTree        = $portalCategoryModel->adminCategoryTree($categoryId);
    //品牌分类
        $brand           = new BrandModel();
        $brand           = $brand->show();
    //商品类型
        $type            = new TypeModel();
        $type            = $type->show();
    //商品属性
        $this->assign('brand',$brand);
        $this->assign('type',$type);
        $this->assign('category_tree', $categoryTree);
    //接收参数
      if($this->request->isPOST()) {
        $arrData=$this->request->param();

				if ($arrData['category_id'] == '0') {
					$this->error('请选择商品分类');
				}
				if ($arrData['brand_id'] == '0') {
					$this->error('请选择商品品牌');
				}
				if ($arrData['lx'] == '0') {
					$this->error('请选择商品规格');
				}
				if ($arrData['gprice'] == null) {
					$this->error('别忘了输入价格!~');
				}

        $is_true=Validate::make()
        ->rule('post_content','require')
        ->check($arrData);
        if ($is_true === false) {
            $this->error('商品详细描述不能为空!');
        }

        $data['brand_id']       = $arrData['brand_id'];
        $data['category_id']    = $arrData['category_id'];
        $data['gname']          = $arrData['gname'];
        $data['content']        = $arrData['descript'];
        $data['type_id']        = $arrData['lx'];
        $data['gprice']         = $arrData['gprice'];
        // $data['vip_price']      = $arrData['vip_price'];
        $data['goods_connect']  = $arrData['post_content'];
        $data['goods_image']  	= $arrData['oneImg'];
        $data['shangjia']  			= $arrData['shangjia'];
        $data['createtime']  		= strtotime($arrData['createtime']);

				//处理图片
        //将图片拼接成一定的
        if (isset($arrData['photos_url'])) {
            $inf = $arrData['photos_url'];
            $infalt = $arrData['photos_alt'];
            foreach ($inf as $key => $value) {
            foreach ($infalt as $k => $v) {
            if ($key == $k) {
            $photos_url[] = $v . '@!@' . $value;
                }
                }
            }
        $photos_url = implode(',',$photos_url);
        } else {
            $photos_url='';
        }
        $data['goods_images']=$photos_url;
				if (isset($arrData['tuijian'])) {
				$data['goods_status']=implode(',',$arrData['tuijian']);
			}else{
					$this->error('推荐状态请您至少添加一项!');
			}




        //将商品的信息存入商品的信息表中
        $goods_id = Db::name('goods')->insertGetId($data);

        $res = $arrData['attr'];

        foreach ($res as $key => $val) {
            $arr =  implode(',', array_unique($val));
            $dataw['goods_property_id']      =$key;
            $dataw['content']                =$arr;
            $dataw['goods_id']               =$goods_id;
            $dataw['goods_type_id']          = $arrData['lx'];
            $goods_attr = Db::name('goods_attr')->insert($dataw);
        }

        if ($goods_attr) {
         $this->success(lang("SAVE_SUCCESS"),'portal/admin_goods/index','',1);

        }
      }
        return $this->fetch();
    }

	public function attrAjax(){

		$id 			 =$this->request->param('id');
		$data 			 = Db::name('attribute')->where('type_id',$id)->select()->toArray();
		foreach ($data as $key => $value) {
			$data[$key]['content']=explode(',',str_replace(PHP_EOL, ',', $value['content']));
		}
		return json_encode($data);
	}


	public function buteAjax(){

		$tid 			= $this->request->param('id');
		$data 			= Db::name('attribute a')->where('type_id',$tid)->select()->toArray();
		foreach ($data as $key => $value) {
			$data[$key]['content']=explode(',',str_replace(PHP_EOL, ',', $value['content']));
		}
		return json_encode($data);
	}
//商品删除
	public function delete(){
			$param = $this->request->param();
			if (isset($param['id'])) {
					$ini = $param['id'];
					$yoy = Db::name('goods')->where('gid',$ini)->delete();
					$yyy = Db::name('goods_attr')->where('goods_id',$ini)->delete();
					if ($yoy&&$yyy) {
							$this->success("删除成功！", '');
					}
				}
	}
//商品修改
	public function edit(){

		$gid = $this->request->param('id');
        if ($this->request->isPOST()) {
                $editData=$this->request->param();
                //将数据进行接收
                $data['brand_id']       = $editData['brand_id'];
                $data['category_id']    = $editData['category_id'];
                $data['gname']          = $editData['gname'];
                $data['content']        = $editData['descript'];
                $data['type_id']        = $editData['lx'];
                $data['gprice']         = $editData['gprice'];
                // $data['vip_price']      = $editData['vip_price'];
                $data['goods_connect']  = $editData['post_content'];
                $data['goods_image']    = $editData['oneImg'];
                $data['shangjia']    = $editData['shangjia'];
								$data['createtime']  		= strtotime($editData['createtime']);


                //处理图片
                //将图片拼接成一定的
                if (isset($editData['photos_url'])) {
                    $inf = $editData['photos_url'];
                    $infalt = $editData['photos_alt'];
                    foreach ($inf as $key => $value) {
                    foreach ($infalt as $k => $v) {
                    if ($key == $k) {
                    $photos_url[] = $v . '@!@' . $value;
                        }
                        }
                    }
                    $photos_url = implode(',',$photos_url);
                } else {
                    $photos_url='';
                }
                $data['goods_images']=$photos_url;

								if (isset($editData['tuijian'])) {
									$data['goods_status']=implode(',',$editData['tuijian']);
								}else{
									$this->error('推荐状态必须填写一项');
								}

                //将商品的信息存入商品的信息表中
                $goods_id = Db::name('goods')->where('gid',$editData['gid'])->update($data);

                //将表里面的数据删除
                $del = Db::name('goods_attr')->where('goods_id',$editData['gid'])->delete();
                $res = $editData['attr'];

                foreach ($res as $key => $val) {
                    $arr =  implode(',', array_unique($val));
                    $dataw['goods_property_id']      =$key;
                    $dataw['content']                =$arr;
                    $dataw['goods_id']               =$editData['gid'];
                    $dataw['goods_type_id']          = $editData['lx'];
                    $goods_attr = Db::name('goods_attr')->insert($dataw);
                }
                if ($goods_attr) {
                    $this->success(lang("SAVE_SUCCESS"),'portal/admin_goods/index','',1);
                }
        }
				$goods = Db::name('goods')->alias('g')
								->field('g.*,b.bname,t.name')
								->join('portal_category t','g.category_id = t.id')
								->join('brand b','g.brand_id = b.id')
								->where('g.gid',$gid)
								->find();

			//商品分类
		    $categoryId 	 = $this->request->param('category', 0, 'intval');
				$portalCategoryModel = new PortalCategoryModel();
				$categoriesTree      = $portalCategoryModel->adminCategoryTree($goods['category_id'], $categoryId);
				$this->assign('categories_tree', $categoriesTree);

			//品牌分类
				$brand 			 = new BrandModel();
				$brand 			 = $brand->show();
			//商品类型
				$type 			 = Db::name('type')->select();
		  	//所有品牌，类别
				$this->assign('brand',$brand);
				$this->assign('type',$type);
			 // 获取商品属性
			 $gooodsAttr=DB::name('goods_attr')->where(['goods_id'=>$gid])->select()->toArray();
			 $attrs=DB::name('attribute')->where("type_id",$goods['type_id'])->select()->toArray();
			 $this->assign('gooodsAttr',$gooodsAttr);
			 $this->assign('attrData',$attrs);

			 //对图片进行处理
        if (strstr($goods['goods_images'],',')) {
            $imgaaa = explode(',',$goods['goods_images']);
            foreach ($imgaaa as $key => $value) {
                 $name_url[] = explode('@!@',$value);
            }
            //对数据进行再次切割
            foreach ($name_url as $key => $value) {
                $imga[] = ['url'=>$value[1],'name'=>$value[0]];
            }
        } else if(empty($goods['goods_images'])) {
             $imga = null;
        } else {
            $name_url = explode('@!@',$goods['goods_images']);
            $imga[] = ['url'=>$name_url[1],'name'=>$name_url[0]];
        }

				//对状态进行处理
				$inf = $goods['goods_status'];
				$zhuangt  =  explode(',',$inf);
			//	dump($zhuangt);die;

        $this->assign('zhuangt',$zhuangt);
        $this->assign('imagess',$imga);
				$this->assign('goods',$goods);
				$this->assign('gid',$gid);
				return $this->fetch();
			}

			public function listOrder()
	    {
				$ids = $this->request->post("list_orders/a");

				if (!empty($ids)) {
						foreach ($ids as $key => $r) {

						Db::name('goods')->update(['list_order_g'=>$r,'gid'=>$key]);

						}

				}
	       $this->success("排序更新成功！", '');
	    }



}
