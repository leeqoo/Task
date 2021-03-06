<?php

namespace Home\Controller;
use Admin\Controller\AdminController;

class InfoController extends AdminController {

	private $db_query;

	/**
	 * 基类控制器初始化
	 */
	protected function _initialize(){ 
		$CONTROLLER_NAME = $Think.CONTROLLER_NAME;
		$ACTION_NAME  = $Think.ACTION_NAME;
		$NO_LOGIN_CONTROLLER = C('NO_LOGIN_CONTROLLER');
		$NO_LOGIN_METHOD = C('NO_LOGIN_METHOD');
		
		if(!(in_array($CONTROLLER_NAME,$NO_LOGIN_CONTROLLER) 
		  	 || in_array($ACTION_NAME,$NO_LOGIN_METHOD))){
			$userInfo = $this->isLogin();
			if($userInfo){
				//此处不能直接重定向到一个集成本类的控制类方法 否则死循环
				//$this->redirect('Task/main');
				$this->assign('user',$userInfo);
			}else{
				$this->display('Info:login');
				exit();
			}
		}
		
		//加载系统配置
		$this->sysConfig();
		
		//配置信息加载 并且缓存起来
		$this->typeConfig();

		//加载店铺信息
		$this->shopConfig();

	    //用户DB查询
	     $this->dbQuery();
	}

	//登陆
	public function login(){
		if(IS_POST){
			$userInfo = parent::login();
			if($userInfo){
				if($userInfo['status']==0){
					$this->error('登录失败,失效用户不允许登陆,请联系我们');
				}else{
					session('user_auth',$userInfo);
					$this->assign('user',$userInfo);
					$this->redirect('Info/main');	
				}
			}else{
				$this->error('登录失败,用户名或密码错误');
			}
		}else{
			$this->display('login');
		}
	}

	//退出
	public function loginOut(){
		session('user_auth',null);
		session(null);
		$this->display('login');
	}
	
	public function index(){
		$this->display('main');
	}

	//首页
	public function main(){
		 //所有任务
		 $tk = M('taskList');
		 if($_SESSION['user_auth']['manager']==C('IS_ADMIN')){
		 	$sql = 'SELECT SUM(jd) jd, SUM(dfk) dfk, SUM(dfh) dfh, SUM(dqr) dqr, SUM(wc) wc FROM v_task_info_rpt';
		 	$infoCnt_tmp = $tk->query($sql);
		 	$infoCnt = $infoCnt_tmp[0];
		 	$infoCnt['pm']='全部';
		 }else{
		 	$sql = 'SELECT @row:=@row+1 AS pm,action_user_id,jd,dfk,dfh,dqr,wc FROM v_task_info_rpt,(SELECT  @row:=0) AS it ORDER BY jd DESC';
			 $allTaskCnt = $tk->query($sql);
			 if(is_array($allTaskCnt)){
			 	foreach ($allTaskCnt as $k => $v) {
			 		if(is_array($v)){
			 			if($v['action_user_id']==$_SESSION['user_auth']['mid']){
			 				$infoCnt = $v;
			 			}
			 		}
			 	}
			 }
		 }
		 
		 $this->assign('infoCnt',$infoCnt);
		 $this->display();
	}

	//我的订单
	public function order_list(){
		$state = I('state');
		$this->assign('state',intval($state));
		
		$m   = 'TaskList';
		$t   = 'v_task_list_ext_user as a';
		$f   = 'a.*';
		$w   = '1=1 '; //and list_status=1
		
		$web_id= I('web_id');
		if(!empty($web_id)){$w=$w.' and a.web_id='.$web_id;}
		$shop_id= I('shop_id');
		if(!empty($shop_id)){$w=$w.' and a.shop_id='.$shop_id;}
		
		$search_keys= I('search_keys');
		$search_words= I('search_words');
		
		if(!empty($search_words)){
			$this->assign('search_words',$search_words);
			if($search_keys=='list_id'){
				$w=$w.' and list_id='.intval($search_words);
			}else if($search_keys=='action_user_nc'){
				$w=$w.' and action_nc=\''.$search_words.'\'';
			}else{
				$w=$w.' and tid='.intval($search_words);
			}
		}
		
		if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
			$w=$w.' and a.action_user_id='.$_SESSION['user_auth']['mid'];
		}
		
		if(intval($state)>0){
			$w.=' and a.state='.intval($state);
		}else{
			$w.='';
		}
		
		//全部订单
		$tks = $this->lists($m,$t,$w,'',$f);
		$this->assign('tks',$tks);
		$this->display();
	}

	//保证金充值
	public function bao_amt(){
		$user_db = C('user_db');
		if(floatval($user_db['cg_baozj'])<0){
			$this->error('已经缴纳保证金');
		}else{
			$this->display();
		}
	}

	//提现工单
	public function ti_log($id=0){
		$m   = M('amtLog');

		if(intval($id)>0){
			$amtLog = $m->find($id);
			$amtLog['amt_oper_id']=$_SESSION['user_auth']['id'];
			$re = $this->modify_charge_ti_comp($amtLog);
			if($re['flag']){
				$this->redirect('info/ti_log');
			}else{
				$this->error($re['msg']);
			}
		}

		$t   = 'task_amt_log as a';
		$f   = '*';
		$w   = 'a.amt_status=1';
		$o   = 'a.amt_date desc';

		if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
		 	$w=$w.' and a.user_id='.$_SESSION['user_auth']['id'];
		}

		$logs = $this->lists($m,$t,$w,$o,$f);
		$this->assign('logs',$logs);
		$this->display();
	}

	//提现
	public function ti_amt(){
		if(IS_POST){
			$t = D('amtLog');
			$amtLog = $t->create();
			$amtLog['amt_date']=time();
			
			$re = $this->modify_charge_ti($amtLog);
			//die;

			if($re['flag']){
				$this->redirect('info/ti_log');
			}else{
				$this->error($re['msg']);
			}
		}else{
			$t = D('userAcc');
			//查询是否已经绑定
			$w['use_type']=1;
			$w['user_id']=$_SESSION['user_auth']['id'];
			$q = $t->where($w)->find();
			if(empty($q)){
				$this->error('尚未绑定财富通账号');
				die;
			}
			$this->assign('userAcc',$q);
			$this->display();
		}
		
	}

	//资金记录
	public function pay_log(){
		$m   = 'chargeLog';
		$t   = '';
		$f   = '*';

		$w['type_id']=array('in',array(4,50));

		$typeId = I('type_id');
		if(intval($typeId)>0){
			switch ($typeId) {
				case 1:
					$w['type_id']=array('in',array(50));
					break;
				case 2:
					$w['type_id']=array('in',array(4));
					break;
				case 3:
					$w['type_id']=array('in',array(4));
					break;
				default:
					break;
			}
		}
		$w['oper_id']=$_SESSION['user_auth']['id'];
		$logs = $this->lists($m,$t,$w,'charge_date desc',$f);
		$this->assign('type_id',$typeId);
		$this->assign("logs",$logs);
		$this->display();
	}

	//基本信息
	public function basicInfo(){
		$this->display();
	}

	//收款账户绑定
	public function pay_bind(){
		$t = D('userAcc');

		//查询是否已经绑定
		$w['use_type']=1;
		$w['user_id']=$_SESSION['user_auth']['id'];
		$q = $t->where($w)->find();

		if(IS_POST){
			if(empty($q)){
				$acc = $t->create();
				$acc['status']=1;
				$acc['use_type']=1;
				$acc['create_date']=time();
				$acc['user_id']=$_SESSION['user_auth']['id'];
				if($acc['acc_id']){
					$acc_id=$t->save($acc);
				}else{
					$acc_id = $t->add($acc);
					$acc['acc_id']=$acc_id;
				}
				if($acc_id){
					$this->redirect('info/pay_bind');
				}
			}else{
				$this->error('该用户已经绑定过提现账户，不允许多次绑定，详细请咨询管理员');
			} 
		}

		$this->assign('accInfo',$q);
		$this->display();
	}
}

?>