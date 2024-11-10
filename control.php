<?php
header('Content-Type: application/json');

require "main/emps.php";
require "main/device.php";
require "main/records.php";
require "main/command.php";
require "main/accessweek.php";
require "main/accessday.php";

require "main/db/pdo.php";

//include_once 'config.php';

use FingerPrint\routeEmps;
use FingerPrint\routeDevice;
use FingerPrint\routeRecord;
use FingerPrint\routeCommand;
use FingerPrint\AccessWeek;
use FingerPrint\AccessDay;

if(isset($_GET['route']))
{
    $route=$_GET['route'];
    dispatchrout($route);
}

function dispatchrout($route)
{
    PDOTool::writeLog($route,'GET:');
    
    if($route=="emps")
    {
        $curPage=1;
        if(isset($_GET['pn']))
        {
            $curPage=$_GET['pn'];
        }
        routeEmps::getData($curPage);
        return;        
    }
    else if($route=="device") /*获取所有考勤机*/
    {
        routeDevice::getData();
        return;        
    }
    else if($route=="sendWs") /*采集所有的用户*/
    {
        $sn=$_GET["deviceSn"];
        routeCommand::getUserList($sn);
        return; 
    }
    else if($route=="getUserInfo")
    {
        $sn=$_GET["deviceSn"];
        routeCommand::getUserInfo($sn);
        return; 
    }
    else if($route=="setPersonToDevice") /*	下发所有用户，面向选中考勤机*/
    {
        $sn=$_GET["deviceSn"];
        routeCommand::setUserToDevice2($sn);
        return;
    }
    else if($route=="setUsernameToDevice")
    {
        $sn=$_GET["deviceSn"];
        routeCommand::setUsernameToDevice($sn);
        return;
    }
    else if($route=="initSystem")/*初始化考勤机*/
    {
        $sn=$_GET["deviceSn"];
        routeCommand::initSystem($sn);
        return;
    }
    else if($route=="setAccessWeek") /*添加周时段，面向全部考勤机*/
    {
        $access=new AccessWeek();
        $access->set();
        return;
    }
    else if($route=="setAccessDay") /*添加天时段,面向全部考勤机*/
    {
        $access=new AccessDay();
        $access->set();
        return;
    }
    else if($route=="setLocckGroup")
    {
        routeCommand::setLocckGroup();
        return;
    }
    else if($route=="setUserLock") //Authorize
    {
        $sn=$_GET["deviceSn"];
        routeCommand::setUserLock($sn);
        return;
    }
    else if($route=="sendGetUserInfo") /*获取单个用户*/
    {
        $id=$_GET["enrollId"];
        $bknum=$_GET["backupNum"];
        $sn=$_GET["deviceSn"];
    	
		routeCommand::sendGetUserInfo($id,$bknum,$sn);
        return;
    }
    else if($route=="setOneUser") /*下发单个用户到机器，对选中考勤机*/
    {
        $enrollId=$_GET["enrollId"];
        $bknum=$_GET["backupNum"];
        $sn=$_GET["deviceSn"];
    	
        routeCommand::setOneUser($enrollId,$bknum,$sn);
        return;
    }
    else if($route=="getDeviceInfo")
    {
        $sn=$_GET["deviceSn"];
        routeCommand::getDeviceInfo($sn);
        return ;
    }
    
    else if($route=="openDoor")
    {
        $sn=$_GET["deviceSn"];
        $doorNum=$_GET["doorNum"];
        routeCommand::openDoor($sn,$doorNum);
        return ;
    }
    else if($route=="addPerson")
    {
        routeCommand::addPerson();
        return ;
    }
    else if($route=="getDevLock")
    {
        $sn=$_GET["deviceSn"];

        routeCommand::getDevLock($sn);
        return;
    }

    else if($route=="geUSerLock")
    {
        $enrollId=$_GET["enrollId"];
        $sn=$_GET["deviceSn"];
        routeCommand::geUSerLock($sn,$enrollId);
        return;
    }
    
    else if($route=="cleanAdmin")
    {
        $sn=$_GET["deviceSn"];
     
        routeCommand::cleanAdmin($sn);
        return;
    }
    else if($route=="deletePersonFromDevice") /*从考勤机删除用户*/
    {
        $enrollId=$_GET["enrollId"];
        $sn=$_GET["deviceSn"];
        routeCommand::deletePersonFromDevice($sn,$enrollId);
        return;
    }

    else if($route=="accessDays") /*获取周时间段*/
    {
        AccessDay::get();
        return;
    }
   
    else if($route=="enrollInfo")
    {
        routeCommand::enrollInfo();
        return;
    }
    
    else if($route=="records")
    {
        $curPage=1;
        if(isset($_GET['pn']))
        {
            $curPage=$_GET['pn'];
        }
        routeRecord::getData($curPage);
    }
    else if($route=="getAllLog")
    {
        $sn=$_GET["deviceSn"];
        routeRecord::getAllLog($sn);
        return;
    }
    else if($route=="getNewLog")
    {
        $sn=$_GET["deviceSn"];
        routeRecord::getNewLog($sn);
        return;
    }
    ////---------------------------------
    else if($route=="depts")
    {
        routeCommand::returnFail();
    }
    else if($route=="checkuser")
    {
        routeCommand::returnFail();
    }
    else if($route=="emp") //? id
    {
        routeCommand::returnFail();
    }
    else
        routeCommand::returnFail();

    // emp/id  type:"GET","DELETE" "PUT", 好像没用到
}

?>