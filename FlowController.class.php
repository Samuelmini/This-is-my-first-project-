<?php
/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 2016/3/16
 * Time: 15:11
 */
namespace Home\Controller;
header( 'Content-Type:text/html;charset=utf-8 ');
use Think\Controller;

class FlowController extends Controller {

    public function add() {              //商品添加到购物车
        $goods = D('Home/Goods');

        if(!$goods->find(I('get.goods_id'))) {
            $this->redirect('/');
        }

        $cart = \Home\Tool\CartTool::getIns();

        $cart->add($goods->goods_id,$goods->goods_name,$goods->shop_price);

        $this->redirect('Home/Flow/checkout');
    }

    //把购物车的商品信息展示给用户
    public function checkout() {
        $cart = \Home\Tool\CartTool::getIns();

        $this->assign('goods',$cart->items());
        $this->assign('num',$cart->calcCnt());
        $this->assign('money',$cart->calcMoney());

        $this->display();
    }

    //把购物车信息写入数据库，生成订单
    public function done() {
        //把姓名等订单信息写入订单信息表
        //把订单对应的商品写入订单商品表
        /*
         ordinfo_id 自动生成
         ord_sn   根据年月日生成
         user_id  cookie

         xm      POST
         mobile  POST
         address POST
        note  POST

         paytype   默认
         paystatus 默认

         money : 购物算calcMoney

         ordtime  time()
        */
        //print_r($_POST);
        $ordinfo = M('ordinfo');                         //订单客户表
        $cart = \Home\Tool\CartTool::getIns();

        $ordinfo->create();         //接住POST过来的数据
        $ordinfo->ord_sn =$void =  date('Ymd').mt_rand(1000,9999);      //给数据库表中的数据赋值,下面三行都一个意思
        $ordinfo->user_id = cookie('user_id') ? cookie('user_id') : 0;
        $ordinfo->money =$vmoney = $cart->calcMoney();
        $ordinfo->ordtime = time();

        $ordinfo_id = $ordinfo->add();                 //得到提交订单时的主键id，货品表用

        /*
       ordgoods_id 主键
        ordinfo_id $ordinfo->add()的返回值
        goods_id  从购物车的items里得到的
        goods_name 同上
        shop_price 同上
        num 同上
        */

        $ordgoods = M('ordgoods');                   //订单商品表

        $data = array();
        foreach($cart->items() as $k=>$v) {            //把N个商品添加到商品表
            $row = array();
            $row['ordinfo_id'] = $ordinfo_id;
            $row['goods_id'] = $k;
            $row['goods_name'] = $v['goods_name'];
            $row['shop_price'] = $v['shop_price'];
            $row['num'] = $v['num'];

            $data[] = $row;
        }

        $ordgoods->addAll($data);             //批量添加数据

        $this->assign('void',$void);          //支付订单编号
        $this->assign('vmoney',$vmoney);      //支付金额

        $cbpay = new \Home\Pay\CBPay($void,$vmoney);       //生成支付
        $form = $cbpay->form();
        $this->assign('form',$form);


        $cart->clear();                        //清空购物车
        $this->display();
    }
}