<?php

/**
 * 系统设置
 * @copyright Copyright &copy; 2011, MAOJIAN
 * @author maojianlw@139.com
 * @since 1.0 - 2011-8-22
 */

class SystemController extends CommonController{

    public function indexAction(){
    	$this->display();
    }
    
	/**
     * 显示PHP配置信息
     */
    public function phpinfoAction(){
    	$_ENV = null;
    	phpinfo();
    }
    
    /**
     * 更新表映射
     */
    public function updateTableMappendAction(){
    	if($dbArr = import('Config.DbConfig', false, ROOT_DIR)){
    	    // 在更新ORM映射关系前清空所有的配置文件
    	    rm_dir(DATA_DIR.getCfgVar('cfg_orm_dir').__DS__);
    	    $dbStr = null;
    		foreach($dbArr as $key=>$val){
    		    $ormM = M('ORM', $key);
    			$dbStr .= $val['dbname'].',';
    			if($tables = $ormM->getDb()->tables()){
    				foreach($tables as $table){
    					$ormM->_getFields($table);
    				}
    			}
    		}
    		if($dbStr) $dbStr = rtrim($dbStr, ',');
    		$this->ajaxReturn(200, "已更新数据库关系映射：【{$dbStr}】");
    	}else{
    		$this->ajaxReturn(300, '更新表映射失败!');
    	}
    }
    
    /**
     * 清空缓存数据
     */
    public function clearCacheAction(){
         rm_dir(DATA_DIR.getCfgVar('cfg_cache_dir'));
         $this->ajaxReturn(200, '已清空所有缓存文件！');
    }
    
    
    /**
     * 关于我们
     */
    public function aboutAction(){
    	$this->display();
    }
    
    private function getParamGroup(){
        return array(1=>'站点设置', 2=>'核心设置', 3=>'附件设置', 4=>'性能选项', 6=>'互动设置', 5=>'其他选项', 7=>'接口设置');
    }
    
    private function getParamType(){
        return array('string'=>'文本', 'number'=>'数字', 'bool'=>'布尔(Y/N)', 'bstring'=>'多行文本');
    }
    
    /**
     * 更新配置文件
     * @param array $data
     */
    private function updateCfg(){
       $list = M('sys_config')->field('varname,value')->order('id ASC')->select();
       if(is_array($list)){
        foreach ($list as $val){
             $data[$val['varname']] = $val['value'];
        }
       }
       return F('SysInfo', $data, DATA_DIR.'Config/');
    }
    
    private function addParam(){
        if($_POST){
           $scModel = M('sys_config');
           if($scModel->getbyVarname($_POST['varname'])){
             $this->ajaxReturn(300, '该变量名已经存在！');
           }
           if($scModel->add()){
             $this->updateCfg();
             $this->ajaxReturn(200, '新增变量成功！');
           }else{
             $this->ajaxReturn(300, '新增变量失败！');
           }
             
        }
        $this->assign('group', $this->getParamGroup());
        $this->assign('type', $this->getParamType());
        $this->display('System/add_param');
    }
    
    private function saveParam(){
        if($_POST){
            $scModel = M('sys_config');
            foreach ($_POST as $k=>$v){
               $scModel->where("varname='{$k}'")->save(array('value'=>$v));
            }
            if($this->updateCfg()){
             $this->ajaxReturn(200, '成功更改参数配置！');
            }else{
             $this->ajaxReturn(300, '配置修改失败！');
            }
        }
    }
    
	/**
     * 参数设置
     */
    public function paramAction(){
        if(isset($_GET['groupid'])){
            $groupid = (int)$_GET['groupid'];
            $list = M('sys_config')->field('varname,value,info,type')->order('id ASC')->where("groupid=$groupid")->select();
            $this->assign('boolArr', array(1=>'是', 0=>'否'));
            $this->assign('list', $list);
            $this->display('System/sys_info');
        }elseif($this->getParameter('action') == 'add'){
            $this->addParam();
        }elseif($this->getParameter('action') == 'save'){
            $this->saveParam();
        }else{
            $this->assign('list', $this->getParamGroup());
            $this->display();
        }
    }
    
    /**
     * 水印设置
     */
    public function markAction(){
         $mark_info = F('Mark/watermark');
         if(count($_POST)){
              if(!empty($_FILES['file']['tmp_name'])){
       		        $upload_boj = new Upload();
       		        $upload_boj->uploadReplace = true;
                    $upload_boj->saveRule = 'mark';
                    $upload_boj->allowTypes = array('image/x-png', 'image/png', 'image/gif');
                    $message = $upload_boj->upload(DATA_DIR.'Mark/');
                    if($message === false){
            			$this->ajaxReturn('300', $upload_boj->getErrorMsg());
            		}
            		$file_info = $upload_boj->getUploadFileInfo();
            		$_POST['img'] = $file_info[0]['savename'];
              }
              F('Mark/watermark',$_POST);
              $this->ajaxReturn(200, '修改成功！');
         }elseif($this->getParameter('target') == 'showImg'){
              $file = DATA_DIR.'Mark/'.$mark_info['img'];
              $image_info = Image::getImageInfo($file);
              header('Content-type:'.$image_info['mime']);
              exit(file_get_contents($file));
         }else{
             $this->assign('info', $mark_info);
             $this->assign('upload_arr', array(1=>'开启', 0=>'关闭'));
             $this->assign('type_arr', array(0=>'gif', 1=>'png', 2=>'文字'));
             $this->assign('position_arr', array(0=>'随机位置', 1=>'顶部居左', 2=>'顶部居中', 3=>'顶部居右', 4=>'左边居中', 5=>'图片中心', 6=>'右边居中', 7=>'底部居左', 8=>'底部居中', 9=>'底部居右'));
             $this->display();
         }
    }
    
}
?>