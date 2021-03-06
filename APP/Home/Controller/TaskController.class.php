<?php

namespace Home\Controller;

class TaskController extends BaseController {
	
	public function index(){
		$this->redirect('task/main');
	}
	
	
	public function go(){
		$id = I('id');
		$this->isComplete($id);
		
		$step = I('step');
		$tk = parent::task($id);
		if($tk){
			if(empty($step)){
				$step = $tk['step'];
			}
			$this->redirect('action_step'.$step.'?id='.$id);
		}else{
			$this->action_step1();
		}	
	}
	
	
	//选择平台
	public function action_step1(){
		
		$userInfo = $this->isLogin();
		if($userInfo){
			if($userInfo['manager']==0){
				$this->error('您的权限为普通买家，不允许发布任务，申请商家账号,联系我们');
			}
		}
		
		$id = I('id');
		$this->isComplete($id);
		
		if(IS_POST ){
			$tk = D('TaskInfo');
			$info = $tk->create();
			if(empty($info['shop_id'])){
				//店铺为空
				$this->error('店铺选择不允许为空,请先绑定店铺',U('User/shop'),2);
			}

			$info['step']=2;
			if($info['is_complete']!=1){
				$info['create_user_id']=$userInfo['id'];
			}
			if(isset($id) && $id>0){
				//修改
				$tk->where('id='.$id)->save($info);
			}else{
				//新增
				$id=$tk->add($info);
				if(empty($id)){
					$this->error('操作失败'.$tk->getError());
				}
			}
			$this->redirect('action_step2?id='.$id);
		}else{
			parent::task($id);	
		}
		parent::shopConfig(true);
		$this->display('action_step1');
	}
	
	public function action_step2(){
		$id = I('id');
		$this->isComplete($id);
		if(IS_POST){
			$tk = D('TaskInfo');
			$info = $tk->create();
			$info['step']=3;
			$info['money']=floatval($info['price'])*floatval($info['cnt']);
			$info['key_cate']=json_encode(I('key_cate'));
			$info['name']=json_encode(I('name'));
			$info['url']=json_encode(I('url'));
			$info['pro_id']=json_encode(I('pro_id'));
			$info['img']=json_encode(I('img'));
			$info['msg']=json_encode(I('msg'));
			
			if(isset($id) && $id>0){
				$tk->where('id='.$id)->save($info);
			}
			$this->redirect('action_step3?id='.$id);
		}else{
			$tk = parent::task($id);	
			if($tk){
				$msgs = json_decode($tk['msg']);
				$this->assign('msgs',$msgs);
			}
		}
		$this->display('action_step2');
	}
	
	public function action_step3(){
		$id = I('id');
		$this->isComplete($id);
		if(IS_POST ){
			$tk = D('TaskInfo');
			$info = $tk->create();
			$info['step']=4;
			if(isset($id) && $id>0){
				$tk->where('id='.$id)->save($info);
			}
			$this->redirect('action_step4?id='.$id);
		}else{
			$tk = parent::task($id);	
			if($tk){
				$web = parent::config($tk['web_id']);
				$shop = parent::config($tk['shop_id']);
				$msg = parent::config($tk['msg_id']);
				$this->assign('web',$web);
				$this->assign('shop',$shop);
				$this->assign('msg',$msg);
			}
		}
		
		parent::typeConfig();
		$this->display('action_step3');
	}
	
	public function action_step4(){
		$id = I('id');
		$this->isComplete($id);
		if(IS_POST){
			$tk = D('TaskInfo');
			$info = $tk->create();
			$info['step']=5;
			$info['buy_yongjin']=5; //暂时默认为5元
			if(empty($info['db_id'])){
				$info['db_id']=date('YmdHis',time()).rand();
			}
			$info['set_comments_txt']=json_encode(I('set_comments_txt'));
			if(isset($id) && $id>0){
				$tk->where('id='.$id)->save($info);
			}
			$this->redirect('action_step5?id='.$id);
		}else{
			$tk = parent::task($id);
			if($tk){
				$comments = json_decode($tk['set_comments_txt']);
				$this->assign('comments',$comments);
			}	
		}
		parent::typeConfig();
		$this->display('action_step4');
	}
	
	public function action_step5(){
		$userInfo = $this->isLogin();
		if($userInfo){
			if($userInfo['manager']==0){
				$this->error('您的权限为普通买家，不允许发布任务，请申请商家账号');
			}
		}
		$id = I('id');
		$this->isComplete($id);
		if(IS_POST ){
			$tk = D('TaskInfo');
			$info = $tk->create();
			$info['step']=6;

			if($info['is_complete']!=1){
				$info['is_complete']=1;
				$info['create_date']=time();
				if(isset($id) && $id>0){
					$tk->where('id='.$id)->save($info);
					
					$task = $tk->find($id);
					$list_num = intval($task['issue_pc'])+intval($task['issue_phone']);
					if(isset($list_num) && $list_num>0){
						
						//生成PC端清单
						for ($i=0;$i<intval($task['issue_pc']);$i++){
							$d[] = array('tid'=>$id,'create_user_id'=>$userInfo['id'],'create_date'=>time(),'list_type'=>'PC','state'=>1);
						}
						
						//生成手机端清单
						for ($i=0;$i<intval($task['issue_phone']);$i++){
							$d[] = array('tid'=>$id,'create_user_id'=>$userInfo['id'],'create_date'=>time(),'list_type'=>'Mobile','state'=>1);
						}
						
						if(!empty($d)){
							$list = M('TaskList');
							$list->addAll($d);
						}
					}
				}
			}
			
			$this->redirect('main');
		}else{
			$tk=parent::task($id);	
			if($tk){
				$tp_total=floatval($tk['transport'])*1*floatval($tk['taocan_number']);
				$total=floatval($tk['price'])*floatval($tk['cnt'])*floatval($tk['taocan_number']);
				$this->assign('tp_total',$tp_total);
				$this->assign('total',$total);
			}
		}
		
		$this->display('action_step5');
	}
	
	public function main(){
		 $m   = 'TaskInfo';
		 $t   = 'v_task_info_ext_cnt as a';
		 $f   = '*';
		 $w   = 'a.status=1';
		 
		 $web_id= I('web_id');
		 if(!empty($web_id)){$w=$w.' and a.web_id='.$web_id;}
		 $shop_id= I('shop_id');
		 if(!empty($shop_id)){$w=$w.' and a.shop_id='.$shop_id;}
		 $msg_id= I('msg_id');
		 if(!empty($msg_id)){$w=$w.' and a.msg_id='.$msg_id;}
		 $action_user_id= I('action_user_id');
		 if(!empty($action_user_id)){$w=$w.' and a.action_user_id='.$action_user_id;}
		 $zd = I('zd');
		 if(!empty($zd)){
		 	switch ($zd){
		 		case 1: $w=$w.' and a.issue_pc>0'; break;
		 		case 2: $w=$w.' and a.issue_phone>0'; break;
		 	}
		 }
		 
		 $query_key= I('query_key');
		 $query_detail=I('query_detail');
		 if(!empty($query_key) && !empty($query_detail)){
		 	switch ($query_key){
		 		//case 1: $w=$w.' and a.list_id='.$query_detail; break;
		 		case 2: $w=$w.' and a.id='.$query_detail; break;
		 	}
		 }

		 $check_flag = I('check_flag');
		 if(!(empty($check_flag))){
		 	$w=$w.' and a.check='.$check_flag.' and is_complete=1';
		 }
		 
		 if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
		 	$w=$w.' and a.create_user_id='.$_SESSION['user_auth']['id'];
		 }
 

		 $tks = $this->lists($m,$t,$w,'',$f);
		 
// 		 $tk = M('TaskList');
// 		 $tks= $tk->table('task_task_list as a , 
// 		 				   task_task_info as b,
// 		 				   task_type_config as c,
// 		 				   task_type_config as d,
// 		 				   task_type_config as e')
// 		 		  ->field('a.*,b.*,c.name as web_name,d.name as shop_name,e.name as msg_name')
// 		 		  ->where('a.tid=b.id 
// 		 		  		   and b.status=1 
// 		 		  		   and b.step=6
// 		 		  		   and b.web_id=c.id
// 		 		  		   and b.shop_id=d.id
// 		 		  		   and b.shop_id=e.id')
// 		 		  //->limit(1,2)
// 		 		  ->select();
		 $this->assign('tks',$tks);
		 
		 //所有任务
		 $where['status']=1;
		 if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
		 	$where['create_user_id']=$_SESSION['user_auth']['id'];
		 }
		 $allTaskCnt = $this->taskCnt('',$where);
		 $this->assign('allTaskCnt',$allTaskCnt);
		 
		 //成功发布任务
		 $where1['status']=1;
		 $where1['is_complete']=1;
		 if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
		 	$where1['create_user_id']=$_SESSION['user_auth']['id'];
		 }
		 $compTaskCnt = $this->taskCnt('',$where1);
		 $this->assign('compTaskCnt',$compTaskCnt);
		 
		 //所有清单
		 $where2['status']=1;
		 if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
		 	$where2['create_user_id']=$_SESSION['user_auth']['id'];
		 }
		 $allListCnt = $this->taskCnt('v_task_list_ext_user',$where2);
		 $this->assign('allListCnt',$allListCnt);
		 
		 $t = M('taskInfo');
		 $sql = "select count(id) tasks,sum(dfk) dfk,sum(dfh) dfh,sum(dqr) dqr,sum(wc) wc from v_task_info_cnt";
		 if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
		 	$sql=$sql.' where create_user_id='.$_SESSION['user_auth']['id'];
		 }
		 $taskInfoCnt = $t->query($sql);
		 $this->assign('taskInfoCnt',$taskInfoCnt);
		 $this->display();
	}
	
	//待付款
	public function dfk(){
		$list_id = I('list_id');
		$this->doFlag();
		$this->redirect('Task/order_info?list_id='.$list_id);
	}
	
	//待发货
	public function dfh(){
		$list_id = I('list_id');
		$this->sendFlag();
		$this->redirect('Task/order_info?list_id='.$list_id);
	}
	
	//待确认
	public function dqr(){
		$list_id = I('list_id');
		$this->sureFlag();
		$this->redirect('Task/order_info?list_id='.$list_id);
	}
	
	//确认
	public function wc(){
		$list_id = I('list_id');
		$this->compFlag();
		$this->redirect('Task/order_info?list_id='.$list_id);
	}
	
	
	//订单列表
	public function order_list(){
		$state = I('state');
		$this->assign('state',intval($state));
		
		$m   = 'TaskList';
		$t   = 'v_task_list_ext_user as a';
		$f   = 'a.*';
		$w   = '1=1 and list_status=1';
		
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
			$w=$w.' and a.create_user_id='.$_SESSION['user_auth']['id'];
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

	//订单导出
	public function order_list_exp(){
		$file_type = "vnd.ms-excel"; // excel表头固定写法 
		$file_ending = "xls"; // excel表的后缀名 
		header("Content-Type: application/".$file_type."; charset=UTF-8"); 
		header("Content-Disposition: attachment; filename=订单列表.$file_ending"); // agentfile到处的表名 
		header("Pragma: no-cache"); // 缓存 
		header("Expires: 0"); 

		$head = "<tr><th>任务编号</th> 
		<th>订单号</th> 
		<th>订单交易号</th> 
		<th>买号</th> 
		<th>押金</th> 
		<th>佣金</th>
		<th>快递名称</th>
		<th>快递单号</th></tr>"; 

		$state = I('state');
		$this->assign('state',intval($state));
		
		$m   = M('TaskList');
		$t   = 'v_task_list_ext_user as a';
		$f   = 'a.*';
		$w   = '1=1 and list_status=1';
		
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
			$w=$w.' and a.create_user_id='.$_SESSION['user_auth']['id'];
		}
		
		if(intval($state)>0){
			$w.=' and a.state='.intval($state);
		}else{
			$w.='';
		}
		
		$records = $m->table($t)->where($w)->field($f)->select();

		if(!empty($records) && is_array($records)){ 
		foreach($records as $k=>$v){ 
				$data.="<tr><td>".$v['tid']."</td> "; 
				$data.="<td>".$v['list_id']."</td> "; 
				$data.="<td></td>"; 
				$data.="<td>".$v['action_user_name']."</td> "; 
				$data.="<td>".$v['yj_pay']."</td>"; 
				$data.="<td>".$v['db_pay']."</td>"; 
				$data.="<td>".$this->getTransPortName($v['transport_name'])."</td>"; 
				$data.="<td>".$v['transport_id']."</td>"; 
				$data.="</tr>"; 
			} 
		} 

		$e = '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
		xmlns:x="urn:schemas-microsoft-com:office:excel" 
		xmlns="http://www.w3.org/TR/REC-html40"> 
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
		<html> 
		<head> 
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" /> 
		<style id="Classeur1_16681_Styles"></style> 
		</head> 
		<body style="background-color:#C0C0C0;"> 
		<div id="Classeur1_16681" align=center x:publishsource="Excel"> 
		<table x:str border=1 cellpadding=0 cellspacing=0 width=60% style="border-collapse: collapse"> 
		'.$head.' 
		'.$data.' 
		</table> 
		</div> 
		</body> 
		</html>'; 
		echo $e; 
		exit;  
	}
	
	//订单详情
	public function order_info(){
		$list_id = I('list_id');
		$t = M('taskList');
		$list = $t->table('v_task_list_ext_user')->where(array('list_id'=>$list_id))->find();
		$this->assign('list',$list);
		$this->display();
	}
	
	
	//任务列表
	public function task_list(){
		$m   = 'TaskInfo';
		$t   = 'v_task_info_ext as a';
		$f   = 'a.*';
		$w   = '1=1 and status=1';

		$web_id= I('web_id');
		if(!empty($web_id)){$w=$w.' and a.web_id='.$web_id;}
		$shop_id= I('shop_id');
		if(!empty($shop_id)){$w=$w.' and a.shop_id='.$shop_id;}
		
		if($_SESSION['user_auth']['manager']!=C('IS_ADMIN')){
			$w=$w.' and create_user_id='.$_SESSION['user_auth']['id'];
		}
		
		$tks = $this->lists($m,$t,$w,'',$f);
		$this->assign('tks',$tks);
		$this->display();
	}
	
	public function task_list_m($tid=0){
		$m   = 'TaskList';
		$t   = 'v_task_list_ext_user as a';
		$f   = 'a.*';
		$w   = 'a.tid='.$tid;
		//全部订单
		$tks = $this->lists($m,$t,$w,'',$f);
		$this->assign('tks',$tks);
		$this->display('task_list_m');
	}
	
	//任务详情
	public function task_info(){
		$t = M('TaskInfo');
		$id = I('id');
		$tk = $t->table('v_task_info_ext')->where(array('id'=>$id))->find();
		$this->assign('tk',$tk);
		
		$t = M('taskInfo');
		$taskInfoCnt = $t->query("select count(id) tasks,sum(dfk) dfk,sum(dfh) dfh,sum(dqr) dqr,sum(wc) wc from v_task_info_cnt where id=".$id);
		$this->assign('taskInfoCnt',$taskInfoCnt);
		
		$this->display();
	}
	
	
	//任务订单修改
	public function modify(){
		$tl = D('TaskList');
		$tlist['list_id']=I('list_id');
		$tlist['transport_name']=I('transport_name');
		$tlist['transport_id']=I('transport_id');
		$tlist['is_send']=1;
		$tlist['send_date']=time();
		$tlist['state']=3;

		$where['list_id']=$tlist['list_id'];
		if($tlist && intval($tlist['list_id'])>0){

			//查找订单信息
			$dlist = $tl->table('v_task_list_ext_user')->where($where)->find();
			if(empty($dlist)){
				$json['msg']='未检索到订单';
				$this->ajaxReturn($json,'json');
				die;
			}

			$charge['list_id']=$tlist['list_id'];
			$charge['db_yongjin']=$dlist['db_yongjin'];
			$charge['db_yj']=floatval($dlist['price'])*floatval($dlist['cnt']);
			$charge['ext_id']=date('YmdHis',time()).rand();
			 
			$userInfo['mid']=$dlist['action_user_id'];
			$userInfo['oper_id']=$_SESSION['user_auth']['id'];
			 
			$res = $tl->where($where)->save($tlist);
			$json['obj']=$res;
			if($res){

				//更新刷客账户信息和资金记录
				$re = $this->modify_charge_bySend($charge,$userInfo);
				//dump($re);
				
				if($re['flag']){
					$json['msg']='更新成功';
					$json['success']=true;
				}else{
					$json['msg']='更新成功,佣金支付故障';
					$json['success']=true;
				}
			//	dump($json);
				//die;
			}else{
				$json['msg']='更新失败';
				$json['success']=false;
			}
		}else{
			$json['msg']='操作失败,'.$tl->getError();
			$json['success']=false;
		}
		 
		$this->ajaxReturn($json);
	}
	
	//任务伪删除 status=0
	public function delTask($id=0){
		if(intval($id)>0){
			$tk = D('taskInfo');
			$d['status']  =0; 
			$where['id']=$id;
			$re = $tk->where($where)->save($d);
			if($re){
				$this->redirect('main');
			}else{
				$this->error('删除失败'.$tk->getError());
			}
		}else{
			$this->error('未选择删除任务');
		}
	}

	//复制任务
	public function cpyTask($id=0){
		$json['msg']='操作失败';
		$json['success']=false;
		if(intval($id)>0){
			$tk = D('taskInfo');
			$info = $tk->find($id);

			$b = $info;
			$b['is_complete']=0;
			$b['step']=5;
			$b['db_pay_flag']=0;
			$b['check']=0;
			$b['db_id']=date('YmdHis',time()).rand();
			$b['db_pay_flag']=0;
			$b['create_date']=null;
			$b['id']=null;
			$re = $tk->add($b);

			if($re){
				$json['msg']='复制成功';
				$json['success']=true;
			}else{
				$json['msg']=$tk->getError();
			}
		}
		$this->ajaxReturn($json,'json');
	}

	//审核
	public function checkTask($id=0){
		$json['msg']='操作失败';
		$json['success']=false;
		if(intval($id)>0){
			$tk = D('taskInfo');
			$info = $tk->find($id);

			$w['id'] = $id;
			$d['check']=intval($info['check'])==0?1:0;
			
			$flag = true;
			//撤销执行之前确认任务是否已经有接单
			if($d['check']==0){
				$t = D('taskList');
				$lre = $t->where('tid='.$id.' and action_user_name is not null')->select();
				if($lre){
					$flag = false;
				}
			}

			if($flag){
				$re = $tk->where($w)->save($d);
				if($re){
					$json['msg']=intval($info['check'])==0?'审核成功':'撤销成功'; 
					$json['success']=true;
				}else{
					$json['msg']=$tk->getError();
				}
			}else{
				$json['msg']='任务已经被接单，不允许撤销';
			}
		}
		$this->ajaxReturn($json,'json');
	}

	//订单管理
	public function order_mg(){
		$m   = 'TaskList';
		$t   = 'v_task_list_ext_user as a';
		$f   = 'a.*';
		$w   = '1=1 and list_status=1';
		
		
		//批量删除
		if(IS_POST){
			$del_datas = I('del_datas');
			if($del_datas=='1'){
				$list_ids = I('list_ids');
				if(is_array($list_ids) && count($list_ids)>0){
					$ww['list_id'] = array('in',$list_ids);
					$mm = D('taskList');
					//$mm->where('list_id in ('.implode(',',$list_ids).')')->delete();
					if(!empty($ww)){
						$mm->where($ww)->delete();
					}
				}
			}
		}

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
			$w=$w.' and a.create_user_id='.$_SESSION['user_auth']['id'];
		}
	 
		//全部订单
		$tks = $this->lists($m,$t,$w,'',$f);
		$this->assign('tks',$tks);

		//店铺信息
		$this->shopConfig();

		$this->display();
	}


	//d币支付
	public function dbPay(){
		$json['success']=false;
		$json['msg']='支付失败';
		if(1==1){
			$id = I('id');
			$json['id']=$id;
			$tk = M('taskInfo');
			$tkInfo=$tk->find($id);
			$json['info']=$tkInfo;
			if($tkInfo){

				//当前D币
				$user_db = $this->dbQuery();
				if(floatval($user_db['cg_amount'])<floatval($tkInfo['db_pay'])){
					$json['msg']='D币余额不足';
				}else{
					//已经为支付状态退回
					if(intval($tkInfo['db_pay_flag'])==0){
						$t = M('userCharge');
						$w['user_id']=$_SESSION['user_auth']['id'];
						$cg['cg_amount']=floatval($user_db['cg_amount'])-floatval($tkInfo['db_pay']);
						$cg['cg_deposit']=floatval($user_db['cg_deposit'])-floatval($tkInfo['yj_pay']);
						$re = $t->where($w)->save($cg);

						if($re){

							$cg = M('chargeLog');
							if(floatval($tkInfo['db_pay'])>0){
								$cglog['tid']=$id;
								$cglog['type_id']=10;
								$cglog['charge']=floatval($tkInfo['db_pay'])*-1;
								$cglog['charge_date']=time();
								$cglog['oper_id']=$_SESSION['user_auth']['id'];
								$cglog['status']=1;
								$cglog['remark']='任务编号:'.$id;
								$cglog['ext_id']=$id;
								$re = $cg->add($cglog);
							}

							if(floatval($tkInfo['yj_pay'])>0){
								$cglog['tid']=$id;
								$cglog['type_id']=30;
								$cglog['charge']=floatval($tkInfo['yj_pay'])*-1;
								$cglog['charge_date']=time();
								$cglog['oper_id']=$_SESSION['user_auth']['id'];
								$cglog['status']=1;
								$cglog['remark']='任务编号:'.$id;
								$cglog['ext_id']=$id;
								$re = $cg->add($cglog);
							}
						}
						
						$tkInfo['db_pay_flag']=1;
						$re = $tk->save($tkInfo);
						if($re){
							$json['success']=true;
							$json['msg']='支付成功';
						}else{
							$json['msg']='变更任务支付状态失败';
						}

					}else{
						$json['msg']='任务已经支付,不建议重复支付';
					}
				}
			}
		}
		$this->ajaxReturn($json,'json');
	}


	//复查
	public function ckEdit($id=0){

		if(IS_POST){
			$tk = D('TaskInfo');
			$info = $tk->create();
			$info['money']=floatval($info['price'])*floatval($info['cnt']);
			$info['key_cate']=json_encode(I('key_cate'));
			$info['name']=json_encode(I('name'));
			$info['url']=json_encode(I('url'));
			$info['pro_id']=json_encode(I('pro_id'));
			$info['img']=json_encode(I('img'));
			$info['msg']=json_encode(I('msg'));
			$info['set_comments_txt']=json_encode(I('set_comments_txt'));
			if($id>0){
				$tk->where('id='.$id)->save($info);
				if($tk){
					$this->redirect('task/main',array('query_key' =>2,'query_detail'=>$id),3,'复查修改成功，跳转中..');
				}else{
					$this->error('操作错误：'.$tk->getError());
				}
			}
		}else{
			$t = M('TaskInfo');
			$id = I('id');
			$tk = $t->table('v_task_info_ext')->where(array('id'=>$id))->find();
			if($tk){
				$msgs = json_decode($tk['msg']);
				$this->assign('msgs',$msgs);
				$web = parent::config($tk['web_id']);
				$shop = parent::config($tk['shop_id']);
				$msg = parent::config($tk['msg_id']);
				$this->assign('web',$web);
				$this->assign('shop',$shop);
				$this->assign('msg',$msg);
				$comments = json_decode($tk['set_comments_txt']);
				$this->assign('comments',$comments);
				$tp_total=floatval($tk['transport'])*1*floatval($tk['taocan_number']);
				$total=floatval($tk['price'])*floatval($tk['cnt'])*floatval($tk['taocan_number']);
				$this->assign('tp_total',$tp_total);
				$this->assign('total',$total);
			}
			$this->assign('tk',$tk);
			$this->display('action_ck');
		}
	}
}

?>