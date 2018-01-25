/**
 * Created by Jerry on 2017/03/21.
 */


//完成bpnp模块的声明
var app = angular.module('bpnp',['ng','ngRoute']);

/*异步请求时禁用缓存*/

app.config(function($httpProvider){
    if(!$httpProvider.defaults.headers.get){
        $httpProvider.defaults.headers.get = {};
    }
    $httpProvider.defaults.headers.common["X-Request-With"] = "XMLHttpRequest";
    $httpProvider.defaults.headers.get['Cache-control'] = "no-cache";
    $httpProvider.defaults.headers.get['Pragma'] = "no-cache";
})


//创建文字缩略显示指令，当文字内容长于一定数量时，缩略显示。
app.directive("shortWords", function() {

    return {
        restrict: "AE",
        replace: true,
        templateUrl: 'tpl/include/shortWords.html',
        scope: {
            textContent: "=",
            maxLength  : "="
        },
        controller: function($scope) {
            $scope.view = {};
            $scope.view.isShowed = false;

            if($scope.textContent && $scope.maxLength){
                $scope.view = {
                    isNeeded: $scope.textContent.length>$scope.maxLength,
                    shortText: $scope.textContent.length<$scope.maxLength?$scope.textContent:$scope.textContent.substring(0,$scope.maxLength-3),
                };
            }

            $scope.toggleStatus = function() {
                $scope.view.isShowed = !$scope.view.isShowed;
            };
        }
    };
});

/*自动填充的捕获 */
app.directive('autoFill', [ function() {
    return {
        require: 'ngModel',
        link:function(scope, element, attr, ngModel) {
            var origVal = element.val();
            if(origVal){
                ngModel.$modelValue = ngModel.$modelValue || origVal;
            }
        }
    };
}]);

/* 增强的input type=checkbox 组件，因为原生的input type=checkbox checked的状态和 ng-checked冲突 */
app.directive("newCheckbox",function(){
    return {
        restrict:"AE",
        replace: true,
        templateUrl: 'tpl/include/newCheckBox.html',
        scope:{
            sourceObj: "="            
        },
        controller: function($scope){           
                      
        }
    }
});


//创建分页指令，用于根据数据记录的总数totalNums + itemsPerPage自动生成对应的页码，并且通过页码，可以控制页面中的数据
app.directive("myPagination",function(){

    return {
        restrict:"AE",
        replace :"true",
        templateUrl:"tpl/include/pagination.html",
        scope:{pageConfig:"=",pageCodes:"="},
        controller:function($scope,$rootScope){

            $scope.itemNumOptions = $rootScope.itemNumOptions;
            $scope.langSet   = $rootScope.langSet;

            /*分页内部的控制器采用普通监听，当每页显示记录条数改变时，则更改分页符的外观,并将起始记录归为0*/
            $scope.$watch('pageConfig.itemsPerPage',function(newVal,oldVal){
                if(newVal!==oldVal){
                    $scope.pageCodes = $rootScope.bornPagerCode($scope.pageConfig);
                    $scope.pageConfig['start']=0;
                }
            });

            /*跳转到首页*/
            $scope.jumpToFirst = function(){
                if($scope.pageConfig.start>0){
                    $scope.pageConfig['start']=0;
                }
            };

            /*跳转到上一页*/
            $scope.jumpToPrev = function(){
                if($scope.pageConfig.start>0) {
                    $scope.pageConfig['start'] = $scope.pageConfig.start - $scope.pageConfig.itemsPerPage;
                }
            };

            /*跳转到指定页
            * 如果page是有效页码数字，则跳到对应的页面
            * 如果page是...，则进行相应的处理：跳到中间的页面，并且将中间的页码显示出来
            * */
            $scope.jumpToPage = function(page){
                if(page>=1 && page<=$scope.pageConfig.totalPages){
                    $scope.pageConfig['start'] = (page-1)*$scope.pageConfig.itemsPerPage;
                    $scope.targetPage = "";
                }
            };

            /*跳转到下一页*/
            $scope.jumpToNext = function(){
                if($scope.pageConfig.totalItems>$scope.pageConfig.itemsPerPage && ($scope.pageConfig.start+$scope.pageConfig.itemsPerPage)<$scope.pageConfig.totalItems) {
                    $scope.pageConfig['start'] = $scope.pageConfig.start + $scope.pageConfig.itemsPerPage;
                }
            };

            /*跳转到最后一页*/
            $scope.jumpToLast = function(){
                if($scope.pageConfig.totalItems>$scope.pageConfig.itemsPerPage && ($scope.pageConfig.start+$scope.pageConfig.itemsPerPage)<$scope.pageConfig.totalItems){
                    $scope.pageConfig['start'] = ($scope.pageConfig.totalPages-1)*$scope.pageConfig.itemsPerPage;
                }
            };
        }
    }
});

/* 创建返回顶部指令，用于控制页面,具有以下功能
    1.当内容超过窗口高度并且向下拉一段时才出现，否则不出现
    2.鼠标移到按钮上方时，按钮本身有动画效果
    3.按钮的透明度随着页面向下拉不断增加
    4.页面回到顶部的过程是有缓动效果的，不是直线，也不是一杆子到底
*/
app.directive('backTop',function(){

    return {
        restrict:"AE",
        replace:true,
        templateUrl:"tpl/include/backToTop.html",
        scope:{},
        controller:function($scope){

            /*处理requestAnimationFrame兼容性*/
            (function() {
                var lastTime = 0;
                var vendors = ['webkit', 'moz'];
                for(var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
                    window.requestAnimationFrame = window[vendors[x] + 'RequestAnimationFrame'];
                    window.cancelAnimationFrame = window[vendors[x] + 'CancelAnimationFrame'] ||    // Webkit中此取消方法的名字变了
                        window[vendors[x] + 'CancelRequestAnimationFrame'];
                }

                if (!window.requestAnimationFrame) {
                    window.requestAnimationFrame = function(callback, element) {
                        var currTime = new Date().getTime();
                        var timeToCall = Math.max(0, 16.7 - (currTime - lastTime));
                        var id = window.setTimeout(function() {
                            callback(currTime + timeToCall);
                        }, timeToCall);
                        lastTime = currTime + timeToCall;
                        return id;
                    };
                }
                if (!window.cancelAnimationFrame) {
                    window.cancelAnimationFrame = function(id) {
                        clearTimeout(id);
                    };
                }
            }());

            /*回到顶部，缓动效果*/
            $scope.backToTop = function(){

                Math.easeOut = function (A, B, rate, callback) {
                    if (A == B || typeof A != 'number') {
                        return;
                    }
                    B = B || 0;
                    rate = rate || 2;

                    var step = function () {
                        A = A + (B - A) / rate;

                        if (A < 1) {
                            callback(B, true);
                            return;
                        }
                        callback(A, false);
                        requestAnimationFrame(step);
                    };
                    step();
                };
                var doc = document.body.scrollTop? document.body : document.documentElement;
                Math.easeOut(doc.scrollTop, 0, 4, function (value) {
                    doc.scrollTop = value;
                });
            };

            /*监测内容和页面，控制按钮本身的出现和透明度变化
            * 04.10:还有一些BUG，按钮的透明度有时会失灵，待改进。
            * */
            var controlButton = function(){
                var screenHeight  = window.innerHeight || document.documentElement.clientHeight;
                var contentHeight = document.body.clientHeight;
                var doc = document.body.scrollTop? document.body : document.documentElement;
                if(screenHeight+100<contentHeight){
                    $(".backToTop").show(1000);
                    window.onscroll = function(){
                        var op =0.2 + (doc.scrollTop/distance)*0.5;
                        $('.backToTop a').css('opacity',op);
                    }
                }
                var distance = contentHeight-screenHeight;

            };
            setTimeout(controlButton,2000);
        }
    }
});


/*创建多选框指令，接受一个数组*/
app.directive("multiSelection",function(){
    return{
        restrict:"AE",
        replace:true,
        templateUrl:'tpl/include/multiSelection.html',
        scope:{
            configParam:"="
        },
        controller:function($scope,$rootScope){

            $scope.toggleStatus = function(){
                $scope.configParam['isShowed'] = !$scope.configParam['isShowed'];
            };

            $scope.updateValue = function(e,id,name){

                var isChecked = $(e.target).prop('checked');

                if(isChecked){
                    $scope.configParam.selectedArr.push(name);
                    $scope.configParam.selectedArrID.push(id);
                }else{
                    var index = $scope.configParam.selectedArr.indexOf(name);
                    $scope.configParam.selectedArr.splice(index,1);
                    $scope.configParam.selectedArrID.splice(index,1);
                }

                if($scope.configParam.selectedArr.length>0){
                    $scope.configParam['selectedOptions'] = $scope.configParam.selectedArr.toString();
                }else{
                    $scope.configParam['selectedOptions'] = $rootScope.langSet.no_limit;
                }

            }
        }
    }
});

//创建一个parentCtrl，父类控制器，用于控制全局变量及方法，包括语言、字库集、用户信息、数据集
app.controller('parentCtrl',["$scope","$location","$http","$rootScope",
        function ($scope,$location,$http,$rootScope) {

            /*根据是不是IE处理兼容性的问题*/
            $rootScope.isIE = (function(){return ("ActiveXObject" in window)})() ;

            /*IE8/IE9要先按F12才有console对象,会导致代码停止执行--兼容性问题*/
            if(navigator.appName.indexOf("Microsoft")!=-1||navigator.userAgent.indexOf("Edge")!=-1){
                window.console = window.console || (function(){
                        var c = {};
                        c.log = c.warn = c.debug = c.info = c.error = c.time = c.dir = c.profile = c.clear = c.exception = c.trace = c.assert = function(){};
                        return c;
                    })();
            }            

            /*默认登录状态为FALSE*/
            $rootScope.isLogged = false;

            /*设置语言相关*/
            /*根据浏览器环境进行判断*/
            $scope.lang = navigator.language?navigator.language:navigator.browserLanguage;

            if($scope.lang.toLowerCase().indexOf("cn") !== -1){
                $scope.lang = "lang-cn"
            }else if($scope.lang.toLowerCase().indexOf("en") !== -1){
                $scope.lang = "lang-en"
            }else if($scope.lang.toLowerCase().indexOf("jp") !== -1){
                $scope.lang = "lang-jp"
            }else{
                $scope.lang = "lang-cn"
            }

            /*如果缓存中已经记录了对应的语言，则选择缓存中的。如果没有，则根据浏览器判断。默认为中文*/
            $rootScope.language = localStorage.getItem("bpnplanguage")||$scope.lang;

            $rootScope.changeLang = function(lang){
                $rootScope.language = lang;
                window.location.reload();
            };

            /*定义函数--根据语言选择对应的字库集*/
            $scope.setLangSet = function(lang){
                switch(lang){
                    case "lang-cn" : $rootScope.langSet = lang_cn; break;
                    case "lang-en" : $rootScope.langSet = lang_en; break;
                    case "lang-jp" : $rootScope.langSet = lang_jp; break;
                    default        : $rootScope.langSet = lang_cn;
                }
            };

            /*调用函数--根据语言选择对应的字库集*/
            $scope.setLangSet($rootScope.language);

            /*设置目前支持的语言选项，在页面中的SELECT OPTION中使用*/
            $rootScope.languages = [{value:"lang-cn",disc:"简体中文"},{value:"lang-en",disc:"English"},{value:"lang-jp",disc:"日本語"}];

            /*设置每页的显示记录的条数*/
            $rootScope.itemNumOptions = [5,10,20,30,50,100,200,500,1000];

            /*监控语言变量，当发生变化时设置相应的字库集，并且保存到缓存中*/

            $scope.$watch('language',function(newValue){
                $scope.setLangSet(newValue);
                localStorage.setItem("bpnplanguage",newValue);
            },true);

            $rootScope.username = "";

            /*检查用户登陆状态，每次刷新时都需要调用，返回promise供调用结束后执行后续函数使用*/
            $rootScope.checkLogStatus = function(){
                var promise = $http({
                    method:"get",
                    url:'../index.php/user_H5/checkIfLogged'                   
                }).success(
                    function(data){
                        $rootScope.isLogged = data;
                        !data &&  ($location.path() != "/login") && $location.path("/login");
                    }
                ).error(function(){
                    $location.path("/login");                    
                });
                return promise;
            };



            /*获取用户的全部信息*/
            $rootScope.getUserInfo = function(){
                var promise = $http({
                    method:"get",
                    url: '../index.php/user_H5/getUserInfo'
                }).success(
                    function(data){
                        if(data['code']==1){
                            $rootScope.userInfo        = data['userInfo'];
                            $rootScope.userInfo['age'] = parseInt($rootScope.userInfo['age']);
                            $rootScope.username        = $rootScope.userInfo["Account"];
                            $rootScope.userGroupID     = parseInt($rootScope.userInfo["GroupID"]);
                            $rootScope.hospitalID      = parseInt($rootScope.userInfo["HospitalID"]);
                            $rootScope.userGroupName   = $rootScope.langSet['userGroup_'+$rootScope.userGroupID];

                            $rootScope.allUserList     = data['allUserList'];

                            /*设置待编辑的基本信息*/
                            $scope.edit = {
                                account  : $rootScope.userInfo['Account'],
                                fullName : $rootScope.userInfo['Name'],
                                age      : parseInt($rootScope.userInfo['Age']),
                                gender   : $rootScope.userInfo['Gender'],
                                info     :$rootScope.userInfo['Info']
                            };

                            /*设置默认留言状态为空*/
                            $scope.feedback = {
                                account:$rootScope.userInfo['Account'],
                                name:$rootScope.userInfo['Name'],
                                status:""
                            };

                        }else{
                            $location.path("/login");
                        }
                    }
                );
                return promise;
            };

            /*获取医院列表，放入到$scope.hospitalLists对象中，主要是医院ID（ID）和医院名称（Name）*/
            /*采集员 + 采集管理员 + 采集组 --userInfo hospitalID所对应的dgsHospital,只有一个*/
            /*分析员 + 分析管理员 + 分析组 --userInfo hospitalID 所对应的 HospitalID, 可能有多个*/

            $rootScope.getHospitalList = function(){
                var promise = $http.get("../index.php/user_H5/getHospitalList")
                    .success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        if(data['code']==1){
                            $rootScope.hospitalList = {};
                            for(var i=0; i<data['result'].length;i++){
                                $rootScope.hospitalList[data['result'][i]['ID']] = data['result'][i]['Name'];
                            }
                        }
                    }).error(function(){
                        $rootScope.checkLogStatus();
                    });
                return promise;
            };

            /*获取用户信息函数*/
            $scope.noBind = false;

            $scope.urlArr = ["/login","/normal","/normal","/admin","/admin","/super","/group","/group"];

            /*根据用户组别跳转到相应的页面*/
            $rootScope.goUrlByGroup = function(groupID){
                $location.path($scope.urlArr[parseInt(groupID)]);
                $rootScope.caseDatas = [];
                $scope.filteredDatas = [];
                $rootScope.applyFilteredData($scope.filteredDatas);
            };

            /*禁止某一组别跳转到其他组别: 根据userGroupID 和 网址进行判断，需要在获取userGroupID之后才能进行正确判断*/
            $rootScope.checkGroupUrl = function(){
                ($scope.urlArr[$rootScope.userGroupID]!==$location.path()) && $location.path("/login");
            };

            /*日期函数，返回对应的日期*/
            $rootScope.date = (function(){
                var y = new Date().getFullYear();
                var m = new Date().getMonth()+1;
                var d = new Date().getDate();
                return y+"-"+m+"-"+d;
            })();

            /*跳转到相应的页面*/
            $rootScope.jump = function (url){
                $location.path(url);
            };

            /*定义MD5加密函数*/
            $rootScope.MD5 = function (string) {

                function RotateLeft(lValue, iShiftBits) {
                    return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
                }

                function AddUnsigned(lX,lY) {
                    var lX4,lY4,lX8,lY8,lResult;
                    lX8 = (lX & 0x80000000);
                    lY8 = (lY & 0x80000000);
                    lX4 = (lX & 0x40000000);
                    lY4 = (lY & 0x40000000);
                    lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
                    if (lX4 & lY4) {
                        return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
                    }
                    if (lX4 | lY4) {
                        if (lResult & 0x40000000) {
                            return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                        } else {
                            return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                        }
                    } else {
                        return (lResult ^ lX8 ^ lY8);
                    }
                }

                function F(x,y,z) { return (x & y) | ((~x) & z); }
                function G(x,y,z) { return (x & z) | (y & (~z)); }
                function H(x,y,z) { return (x ^ y ^ z); }
                function I(x,y,z) { return (y ^ (x | (~z))); }

                function FF(a,b,c,d,x,s,ac) {
                    a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
                    return AddUnsigned(RotateLeft(a, s), b);
                };

                function GG(a,b,c,d,x,s,ac) {
                    a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
                    return AddUnsigned(RotateLeft(a, s), b);
                };

                function HH(a,b,c,d,x,s,ac) {
                    a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
                    return AddUnsigned(RotateLeft(a, s), b);
                };

                function II(a,b,c,d,x,s,ac) {
                    a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
                    return AddUnsigned(RotateLeft(a, s), b);
                };

                function ConvertToWordArray(string) {
                    var lWordCount;
                    var lMessageLength = string.length;
                    var lNumberOfWords_temp1=lMessageLength + 8;
                    var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
                    var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
                    var lWordArray=Array(lNumberOfWords-1);
                    var lBytePosition = 0;
                    var lByteCount = 0;
                    while ( lByteCount < lMessageLength ) {
                        lWordCount = (lByteCount-(lByteCount % 4))/4;
                        lBytePosition = (lByteCount % 4)*8;
                        lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                        lByteCount++;
                    }
                    lWordCount = (lByteCount-(lByteCount % 4))/4;
                    lBytePosition = (lByteCount % 4)*8;
                    lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
                    lWordArray[lNumberOfWords-2] = lMessageLength<<3;
                    lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
                    return lWordArray;
                };

                function WordToHex(lValue) {
                    var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
                    for (lCount = 0;lCount<=3;lCount++) {
                        lByte = (lValue>>>(lCount*8)) & 255;
                        WordToHexValue_temp = "0" + lByte.toString(16);
                        WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
                    }
                    return WordToHexValue;
                };

                function Utf8Encode(string) {
                    string = string.replace(/\r\n/g,"\n");
                    var utfText = "";

                    for (var n = 0; n < string.length; n++) {

                        var c = string.charCodeAt(n);

                        if (c < 128) {
                            utfText += String.fromCharCode(c);
                        }
                        else if((c > 127) && (c < 2048)) {
                            utfText += String.fromCharCode((c >> 6) | 192);
                            utfText += String.fromCharCode((c & 63) | 128);
                        }
                        else {
                            utfText += String.fromCharCode((c >> 12) | 224);
                            utfText += String.fromCharCode(((c >> 6) & 63) | 128);
                            utfText += String.fromCharCode((c & 63) | 128);
                        }

                    }
                    return utfText;
                };

                var x=Array();
                var k,AA,BB,CC,DD,a,b,c,d;
                var S11=7, S12=12, S13=17, S14=22;
                var S21=5, S22=9 , S23=14, S24=20;
                var S31=4, S32=11, S33=16, S34=23;
                var S41=6, S42=10, S43=15, S44=21;

                string = Utf8Encode(string);

                x = ConvertToWordArray(string);

                a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

                for (k=0;k<x.length;k+=16) {
                    AA=a; BB=b; CC=c; DD=d;
                    a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
                    d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
                    c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
                    b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
                    a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
                    d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
                    c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
                    b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
                    a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
                    d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
                    c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
                    b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
                    a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
                    d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
                    c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
                    b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
                    a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
                    d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
                    c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
                    b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
                    a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
                    d=GG(d,a,b,c,x[k+10],S22,0x2441453);
                    c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
                    b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
                    a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
                    d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
                    c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
                    b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
                    a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
                    d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
                    c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
                    b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
                    a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
                    d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
                    c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
                    b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
                    a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
                    d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
                    c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
                    b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
                    a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
                    d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
                    c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
                    b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
                    a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
                    d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
                    c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
                    b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
                    a=II(a,b,c,d,x[k+0], S41,0xF4292244);
                    d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
                    c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
                    b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
                    a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
                    d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
                    c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
                    b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
                    a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
                    d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
                    c=II(c,d,a,b,x[k+6], S43,0xA3014314);
                    b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
                    a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
                    d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
                    c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
                    b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
                    a=AddUnsigned(a,AA);
                    b=AddUnsigned(b,BB);
                    c=AddUnsigned(c,CC);
                    d=AddUnsigned(d,DD);
                }

                var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);

                return temp.toLowerCase();
            };

            /*退出时进行相应的操作*/
            $rootScope.exit = function(){
                document.cookie     = "";
                $rootScope.islogged = false;
                sessionStorage.clear();
                $rootScope.exited = true;                
               if($location.path()!="/login"){
                   $location.path("/login");
               }
               $http({
                   method:"get",
                   url:'../index.php/user_H5/toExit'
                }).success(function(data){
               });
            };

            /*读取数据产品信息: 由于有all选项，直接改为定义，不再从数据库中获取，且可减少ajax次数*/
            $scope.recordTypes = [
                {ID: "",  Name: "all",    Locked: "0", Info: "all"},
                {ID: "1", Name: "Holter", Locked: "0", Info: "动态心电图"},
                {ID: "2", Name: "PCECG",  Locked: "0", Info: "静息心电图"},
                {ID: "3", Name: "ABPM",   Locked: "0", Info: "动态血压"}
            ];


            /*设置性别选项：也不再从数据库中读取*/
            $scope.genderList = $rootScope.langSet['gender_list'];

            /*设置报告状态选项*/
            $scope.recordStatus = $rootScope.langSet['report_status'];

            /*定义函数，根据config生成对应的换页符编码,
             */
            $rootScope.bornPagerCode = function(config){
                var codesArr = [];
                config.totalPages = Math.ceil(config.totalItems/config.itemsPerPage);
                for(var i=1;i<=config.totalPages;i++){
                    codesArr.push(i);
                }
                return codesArr;
            };

            /*当pagerConfig改变时*/

            $rootScope.period = {
                today      : "today",
                yesterday  : 'yesterday',
                twoDaysAgo : 'twoDaysAgo',
                thisWeek   : 'thisWeek',
                lastWeek   : 'lastWeek',
                thisMonth  : 'thisMonth',
                lastMonth  : 'lastMonth',
                thisSeason : 'thisSeason',
                lastSeason : 'lastSeason',
                thisYear   : 'thisYear',
                lastYear   : 'lastYear',
                all        : 'all'
            };

            var formatDate = function(date){
                date = new Date(date);
                var y = date.getFullYear(),m = date.getMonth()+1,d = date.getDate();
                (m < 10) && (m = "0" + m);
                (d < 10) && (d = "0" + d);
                return (y+"/"+m+"/"+d);
            };

            $rootScope.formatDate = formatDate;

            var getMonthDays = function(m){
                var y = new Date().getFullYear(), m_start = new Date(y,m,1), m_end = new Date(y,m+1,1);
                return (m_end - m_start)/(1000*60*60*24);
            };

            /*根据word返回相应的时间对象*/
            $rootScope.datePeriodOfWord = {
                today        : {start:formatDate(new Date())+" 00:00:00",end:formatDate(new Date())+" 23:59:59"},
                yesterday    : {start:formatDate(new Date().setDate(new Date().getDate()-1))+" 00:00:00",end:formatDate(new Date().setDate(new Date().getDate()-1))+" 23:59:59"},
                twoDaysAgo   : {start:formatDate(new Date().setDate(new Date().getDate()-2))+" 00:00:00",end:formatDate(new Date().setDate(new Date().getDate()-2))+" 23:59:59"},
                thisWeek     : {start:formatDate(new Date(new Date().getFullYear(),new Date().getMonth(),new Date().getDate()-new Date().getDay()))+" 00:00:00",end:formatDate(new Date(new Date().getFullYear(),new Date().getMonth(),new Date().getDate()+6-new Date().getDay()))+" 23:59:59"},
                lastWeek     : {start:formatDate(new Date(new Date().getFullYear(),new Date().getMonth(),new Date().getDate()-new Date().getDay()-7))+" 00:00:00",end:formatDate(new Date(new Date().getFullYear(),new Date().getMonth(),new Date().getDate()-1-new Date().getDay()))+" 23:59:59"},
                thisMonth    : {start:formatDate(new Date(new Date().getFullYear(),new Date().getMonth(),1))+" 00:00:00",end:formatDate(new Date())+" 23:59:59"},
                lastMonth    : {start:formatDate(new Date(new Date().getFullYear(),new Date().getMonth()-1,1))+" 00:00:00",end:formatDate(new Date(new Date().getFullYear(),new Date().getMonth()-1,getMonthDays(new Date().getMonth()-1)))+" 23:59:59"},
                thisSeason   : {start:formatDate(new Date(new Date().getFullYear(),Math.floor((new Date().getMonth()-1)/3)*3,1))+" 00:00:00",end:formatDate(new Date())+" 23:59:59"},
                lastSeason   : {start:formatDate(new Date(new Date().getFullYear(),Math.floor((new Date().getMonth()-1)/3-1)*3,1))+" 00:00:00",end:formatDate(new Date(new Date().getFullYear(),Math.ceil((new Date().getMonth()-1)/3)*3,0))+" 23:59:59"},
                thisYear     : {start:formatDate(new Date(new Date().getFullYear(),0,1))+" 00:00:00",  end:formatDate(new Date())+" 23:59:59"},
                lastYear     : {start:formatDate(new Date(new Date().getFullYear()-1,0,1))+" 00:00:00",end:formatDate(new Date(new Date().getFullYear(),0,0))+" 23:59:59"},
                all          : {start:formatDate(new Date(2011,0,1))+" 00:00:00",end:formatDate(new Date())+" 23:59:59"}
            };

            /*下载excel文件,需要实现多语言功能，所以需要对数据进行预处理，先增加一栏国际化表头*/
            $scope.excelFile = function(filteredDataArr){

                $scope.excelTitle = [{
                    DataID             : $rootScope.langSet.record_id_label,
                    PatientID          : $rootScope.langSet.patient_id_label,
                    PatientName        : $rootScope.langSet.patient_name_label,
                    PatientGender      : $rootScope.langSet.gender_label,
                    PatientGenderDesc  : $rootScope.langSet.gender_label,
                    DataTypeID         : $rootScope.langSet.record_type_label,
                    DataType           : $rootScope.langSet.record_type_label,
                    DGSHospitalID      : $rootScope.langSet.upload_hospital_label,
                    DGSHospitalName    : $rootScope.langSet.upload_hospital_label,
                    SubmitTime         : $rootScope.langSet.submit_time_label,
                    DataClinic         : $rootScope.langSet.record_clinic_label,
                    DiagnosedTime      : $rootScope.langSet.dgs_time_label,
                    DiagnosingTime     : $rootScope.langSet.dgs_time_label,
                    DGSUserID          : $rootScope.langSet.dgs_user_label,
                    DGSUserName        : $rootScope.langSet.dgs_user_label,
                    DataInfo           : $rootScope.langSet.info_label,
                    DGSResult          : $rootScope.langSet.dgs_result_label,
                    Flag               : $rootScope.langSet.dgs_result_label,
                    Status             : $rootScope.langSet.status_label,
                    StatusDesc         : $rootScope.langSet.status_label
                }];

                var excelData = JSON.stringify($scope.excelTitle.concat(filteredDataArr));

                $http({
                    method:"POST",
                    url:"data/excel_create.php",
                    data: $.param({excel:excelData}),
                    headers:{'Content-Type':'application/x-www-form-urlencoded',"charset":"utf-8"}
                }).success(function(url){
                    $scope.downloadURL = url;
                    window.open(url);
                }).error(function(){
                    $rootScope.checkLogStatus()
                })
            };

            $scope.singleCase = {};

            /*当点击某一病例后显示 相应的病例信息*/
            $scope.showCaseById = function(caseData,e){
                $scope.repealStatus = "";
                if(typeof(caseData)=="object"){
                    $scope.singleCase = caseData;
                }
                $scope.singleCaseAction = [];
                $http({
                    method : "get",
                    url    : "../index.php/user_H5/getCaseActions/"+caseData['infoID']
                }).success(function(data){
                    (data == "offline") && $rootScope.exit();
                        if(data['code']==1){
                        $scope.singleCaseAction = data['result'];
                        }
                        $scope.actionNoRecord = data['noRecord'];
                 }).error(function(){
                    $rootScope.checkLogStatus()
                })
            };

            /*复杂查询页面的分页符*/
            $scope.pagerConfig0 = {
                itemsPerPage:20,
                totalItems:0,
                start:0,
                totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
            };

            /*设置筛选条件*/
            $scope.multiFilter = {
                startDate  : new Date("2011/01/01"),
                endDate    : new Date(),
                timePeriod : 'all'
            };

            /*默认数据为空*/
            $scope.searchedDatas = [];

            /*从数据库按照条件检索所有记录*/
            $scope.searchAllRecords = function(){
                
                $scope.multiFilter['start'] = $scope.multiFilter['startDate'].getTime();
                $scope.multiFilter['end']   = $scope.multiFilter['endDate'].getTime();

                $scope.reFilter = {};
                $http({
                    method  : "POST",
                    url     : "../index.php/user_H5/searchAllRecords",
                    data    : $.param($scope.multiFilter),
                    headers : {'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
                }).success(function(data){
                    (data == "offline") && $rootScope.exit();
                        if(data['code']=="1"){
                            $scope.filteredDatas = $scope.searchedDatas = $rootScope.shapeData(data['result']);
                        }else{
                            $scope.filteredDatas = $scope.searchedDatas = [];
                        }
                        $rootScope.applyFilteredData($scope.filteredDatas);
                    }).error(function(){
                        $rootScope.checkLogStatus();
                })
            };

            /*对检索过的数据进行预处理，便于导出EXCEL文件*/
            $rootScope.shapeData = function(dataArr){
                if(dataArr && dataArr.length){
                    dataArr.forEach(function(dataObj){
                        dataObj['DataType']          = $scope.recordTypes[dataObj['DataTypeID']]['Name'];
                        dataObj['PatientGenderDesc'] = $scope.genderList[dataObj['PatientGender']];
                        dataObj['DGSUserName']       = $scope.allUserList[dataObj['DGSUserID']];
                        dataObj['UserName']          = $scope.allUserList[dataObj['UserID']];
                        dataObj['DGSHospitalName']   = $scope.hospitalList[dataObj['DGSHospitalID']];
                        dataObj['HospitalName']      = $scope.hospitalList[dataObj['HospitalID']];
                        dataObj['StatusDesc']        = $rootScope.langSet['data_status1'][dataObj['Status']];
                    })
                }
                return dataArr;
            };

            /*采用深度监听，监听每个pagerConfig对象，当config对象发生改变时，数据也会相应地变化*/
            $scope.$watch('pagerConfig0',function(newVal,oldVal){
                if(newVal!=oldVal){
                    if($scope.filteredDatas && ($scope.filteredDatas.length>0)){
                        $scope.pageFilteredDatas = $scope.filteredDatas.slice($scope.pagerConfig0.start,$scope.pagerConfig0.start+parseInt($scope.pagerConfig0.itemsPerPage));
                    }else{
                        $scope.pageFilteredDatas = [];
                    }
                }
            },true);

            /*监控筛选条件中的radio按钮，当发生改变时则更改multiFilter对象中的数据*/
            $scope.$watch('multiFilter.timePeriod',function(newTime,oldTime){
                if(newTime!=oldTime){
                    $scope.multiFilter['startDate'] = new Date($rootScope.datePeriodOfWord[newTime]['start']);
                    $scope.multiFilter['endDate']   = new Date($rootScope.datePeriodOfWord[newTime]['end']);
                }
            });

            /*当获取filtered数据后根据数据改变相应的内容*/
            $rootScope.applyFilteredData = function(datas){
                $scope.pagerConfig0.totalItems = datas.length;
                $scope.pagerConfig0.start      = 0;
                $scope.pageFilteredDatas       = datas.slice(0,$scope.pagerConfig0.start+$scope.pagerConfig0.itemsPerPage);
                $scope.pagerCodes0 = $rootScope.bornPagerCode($scope.pagerConfig0);
            };

            /*定义筛选的参数对象*/
            $scope.reFilter = {};

            /*这里对整个筛选对象进行深度监听*/
            $scope.$watch('reFilter',function(newVal,oldVal){
                if(newVal!=oldVal){
                    $scope.multiSearch($scope.searchedDatas);
                }
            },true);

            /*数据过滤*/
            $scope.multiSearch = function(datasArr){
                /*此处代码可精简，对对象进行遍历*/
                $scope.downloadURL = "";
                $scope.filteredDatas = datasArr;
                var tempArr = [];
                var searched = false;

                for(key in $scope.reFilter){
                    var val = $.trim($scope.reFilter[key]);
                    if(val){
                        tempArr = [];
                        (key=="SubmitTime" || key=="'DiagnosingTime") && (val = $rootScope.formatDate(val).replace(/\//g,"-"));
                        datasArr.forEach(function(valObj){
                            (valObj[key].indexOf(val)!=-1) && tempArr.push(valObj);
                        });
                        datasArr = tempArr;
                        searched = true;
                    }
                }
                $scope.filteredDatas = searched?tempArr:$scope.searchedDatas;
                $rootScope.applyFilteredData($scope.filteredDatas);
            };

            /*重置筛选条件*/
            $scope.resetMultiFilter = function(){
                $scope.multiFilter = {
                    startDate     : new Date("2011/01/01"),
                    endDate       : new Date(),
                    timePeriod    : 'all'
                };
                $scope.filteredDatas = $scope.searchedDatas = [];
                $rootScope.applyFilteredData([]);
                $scope.reFilter = {};
            };

            /*档案编辑默认为不在编辑状态*/
            $scope.profileEditing = false;

            /*显示编辑页面*/
            $scope.showEditProfile = function(){
                $scope.profileEditing = true;
                $scope.edit['status'] = "";
            };

            /*关闭编辑页面*/
            $scope.hideEditProfile = function(){
                $scope.profileEditing = false;
            };

            /*编辑我的档案：当提交数据时*/
            $scope.toEditProfile = function(){
                $scope.edit['status']="";

                if($scope.edit['newPwd']!=$scope.edit['newPwd2']){
                    $scope.edit['status'] = $rootScope.langSet['password_must_same'];
                    return false;
                }

                $scope.edit['oldPwd'] &&($scope.edit['oldPwd'] = $rootScope.MD5($scope.edit['oldPwd']));
                $scope.edit['newPwd'] &&($scope.edit['newPwd'] = $rootScope.MD5($scope.edit['newPwd']));
                $scope.edit['newPwd2']&&($scope.edit['newPwd2']= $rootScope.MD5($scope.edit['newPwd2']));


                $http({
                    method : 'POST',
                    data   : $.param($scope.edit),
                    url    : '../index.php/user_H5/editProfile',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
                })
                    .success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        $scope.edit['oldPwd'] = "";
                        $scope.edit['newPwd'] = "";
                        $scope.edit['newPwd2']= "";
                        if(data['code']==1){
                            $scope.edit['status'] = $rootScope.langSet['update_success'];
                            /*更新当前的用户信息*/
                            $rootScope.userInfo = data['userInfo'];
                        }else if(data['code']==-1){
                            $scope.edit['status'] = $rootScope.langSet['old_password_need'];
                        }else if(data['code']==-2){
                            $scope.edit['status'] = $rootScope.langSet['password_must_same'];
                        }else{
                            $scope.edit['status'] = $rootScope.langSet['update_failed'];
                        }
                    }).error(function(){
                        $rootScope.checkLogStatus();
                })
            };

            /*重置档案编辑*/
            $scope.resetEditProfile = function(){
                $scope.edit = {
                    account  : $rootScope.userInfo['Account'],
                    fullName : $rootScope.userInfo['Name'],
                    age      : parseInt($rootScope.userInfo['Age']),
                    gender   : $rootScope.userInfo['Gender'],
                    info     :$rootScope.userInfo['Info']
                };
            };

            /*对对象数组进行排序*/
            $rootScope.sortDataByKey = function(dataArray,key,flag){
                if(flag==1){
                    dataArray.sort(function(a,b){
                        return a[key]<b[key]?1:(a[key]==b[key]?0:-1);
                    })
                }else if(flag==-1){
                    dataArray.sort(function(a,b){
                        return a[key]>b[key]?1:(a[key]==b[key]?0:-1);
                    })
                }
                return dataArray;
            };

            /*简易排序：定义排序关键词，默认为submitTime*/
            $scope.sortByWord = 'ID';
            /*简易排序：定义正反序FLAG*/
            $scope.descToggle = 0;
            /*对数据进行简单排序*/
            $scope.easySort = function(event,type){

                var t = $(event.target);
                $scope.sortByWord = type;
                if(t.attr("class").indexOf("down")!=-1){
                    $scope.descToggle = 0;
                    t.removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-up");
                }else{
                    $scope.descToggle = 1;
                    t.removeClass("glyphicon-chevron-up").addClass("glyphicon-chevron-down");
                }
            };


            /*当点击某一账号后显示 相应的账号信息*/
            $scope.noBind = false;
            $scope.showAccountInfo = function(accountData){
                /*设置默认不编辑*/
                $rootScope.singleAccountEditing = false;
                if(typeof(accountData)=="object"){
                    $scope.singleAccountData = accountData;
                    if(accountData.Account.length>0 && accountData['GroupID']==2){
                        $http({
                            method:"get",
                            url: "../index.php/user_H5/getSingleAccountBind/"+accountData.ID
                        }).success(function(data){
                                if(data=="offline"){
                                    $location.path("/login");
                                    return false;
                                }
                                if(data['accountBind'] && data['accountBind'].length>0){
                                    $scope.singleAccountBind = data['accountBind'];
                                    $scope.noBind = false;
                                }else{
                                    $scope.singleAccountBind = [];
                                    $scope.noBind = true;
                                }
                            }).error(function(){
                                $rootScope.checkLogStatus();
                        })
                    }
                }
            };

            /*病历的操作*/
            $scope.repealStatus = "";
            $scope.repealCase = function(infoID,type){
                $http({
                    method:"get",
                    url: "../index.php/user/repeal_rec_data?RepealType="+type+"&ID="+infoID
                }).success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        $scope.repealStatus = (data=="true"?$rootScope.langSet['success_label']:$rootScope.langSet['failed_label']);
                        /*需要重新刷新数据TODO*/
                        $scope.updateCaseData(infoID);
                    })
                    .error(function(){
                        $scope.repealStatus = $rootScope.langSet['failed_label'];
                        $rootScope.checkLogStatus();
                    });
            };

            /*当对数据进行操作后，如放弃分析，更新相应地数据*/
            $scope.updateCaseData = function(dataID){
                var index = 0;
                if($scope.filteredDatas && $scope.filteredDatas.length){
                    $scope.filteredDatas.forEach(function(data){
                        if( data.infoID == dataID ){
                            $scope.filteredDatas.splice(index,1);
                        }
                        index++;
                    });
                    $rootScope.applyFilteredData($scope.filteredDatas);

                }
                index = 0;
                if($rootScope.caseDatas && $rootScope.caseDatas.length){
                    $scope.caseDatas.forEach(function(data){
                        if( data.infoID == dataID ){
                            $scope.caseDatas.splice(index,1);
                        }
                        index++;
                    })
                }
            };

            /*提交留言*/
            $scope.feedbackStatus = "";
            $scope.submitFeedback = function(bool){
                if(!bool){
                    $http({
                        url:"../index.php/user_H5/submitFeedback",
                        method:"POST",
                        data: $.param($scope.feedback),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
                    }).success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        $scope.feedback['status'] = (data==1? $rootScope.langSet.success_label : $rootScope.langSet.failed_label);
                        $scope.feedback['content'] = "";
                    }).error(function(){
                        $rootScope.checkLogStatus();
                    })
                }
            };

            /*清空意见反馈表*/
            $scope.clearFeedback = function(){
                $scope.feedback = {
                    account:$rootScope.userInfo['Account'],
                    name:$rootScope.userInfo['Name'],
                    phone:"",
                    email:"",
                    content:"",
                    status:""
                };
                $scope.feedbackStatus = "";
            };

            /*获取留言列表*/
            $scope.getMessageList = function(){
                $http.get("../index.php/user_H5/getMessageList")
                    .success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        if(data['code']==1){
                        $scope.messageList = data['suggestion'];
                        }
                    }).error(function(){
                        $rootScope.checkLogStatus();
                    })
            }
        }
    ]);

//声明loginCtrl，用于控制登录，屏蔽不合理的输入，且具有密码错误提醒功能，以提高用户友好度
app.controller('loginCtrl',['$scope','$rootScope','$http',
    function($scope,$rootScope,$http){

        /*登录结果提醒变量 loginTip*/
        $scope.loginTip = "";
        $(".modal-backdrop").hide();
        $rootScope.isLogged = false;
        !$rootScope.exited && $rootScope.exit();
        /*当输入框内容变化时，登录结果提醒为空*/
        $scope.inputChange = function(){
            $scope.loginTip = "";            
        };

        /*监督login页面中的language变量，发生改变时传递给父控制器*/
        $scope.$watch('language',function(newValue,oldValue){
            newValue!=oldValue && ($rootScope.language = newValue);
        });        

        setTimeout(function(){
             $scope.userAccount = $scope.userAccount || $("#userAccount").val();
             $scope.userPwd = $scope.userPwd || $("#userPwd").val();
        },1000)       

        /*进行登录，密码使用MD5加密，和数据库中进行比对。匹配则为登录成功，不匹配则进行相应的提示*/
        $scope.toLog = function(){

            var userAccount = $scope.userAccount;
            if($scope.userPwd.length>=3 && $scope.userPwd.length>=6){
                $scope.userPwd = $rootScope.MD5($scope.userPwd);
            }else{
                $scope.loginTip = $rootScope.langSet['loginTip_-1'];
                return false;
            }
            if(userAccount.length>=3 && $scope.userPwd.length==32){
                $http.get("../index.php/user_H5/toLogin?userAccount="+userAccount+"&userPwd="+$scope.userPwd)
                    .success(function(data){
                        $scope.loginTip = $rootScope.langSet['loginTip_'+data];
                        if(parseInt(data)==1){                            
                            $rootScope.isLogged  = true;
                            var promise = $rootScope.getUserInfo();
                            promise.then(function(){
                               $rootScope.goUrlByGroup($rootScope.userGroupID);
                            });                            
                        }else{
                            $scope.userPwd = "";
                        }
                    })
                    .error(function(data){
                        $scope.loginTip = $rootScope.langSet['loginTip_default'];
                        $scope.userPwd  = "";
                    })
            }else{
                $scope.loginTip = $rootScope.langSet['loginTip_-1'];
                $scope.userPwd  = "";
            }
        }
}]);

//声明控制器normalCtrl,对应main.html 功能是控制分析员 + 操作员的页面以及数据逻辑
app.controller('normalCtrl',['$scope','$http','$routeParams','$rootScope','$location',
    function($scope,$http,$routeParams,$rootScope,$location){

        /*检查用户登录状态，并且获取用户信息*/
        var promise0 = $rootScope.checkLogStatus();

        /*检查完登录状态之后 再获取相对应的数据*/
        promise0.then(function(){
            if($rootScope.isLogged){
                var promise1 = $rootScope.getUserInfo();
                promise1.then(function(){
                    /*检查URL和用户组别是否相配*/
                    $rootScope.checkGroupUrl();

                    $rootScope.userGroupID==2 && $scope.getAccountBind();
                   
                    var promise2 = $rootScope.getHospitalList();
                    promise2.then(function(){
                        /*获取相应的1000条病例记录*/
                       var promise3 = $scope.getDefaultCase();
                       promise3.then(function(){
                           /*配置相应的页面 */
                            $scope.tabs = [
                                {title: $rootScope.userGroupID==1?$rootScope.langSet['title_my_rec_data']:$rootScope.langSet['title_my_report'], url: 'tpl/basePage/dataView.html'},
                                {title: $rootScope.userGroupID==1?$rootScope.langSet['title_search_my_record']:$rootScope.langSet['title_search_aly_report'], url: 'tpl/basePage/caseSearchNormal.html'},
                                {title: $rootScope.langSet['title_my_profile'], url: 'tpl/basePage/profile.html'}
                            ];

                            /*定义当前面：默认为第一个标签页*/
                            $scope.currentTab = $scope.tabs[0]['url'];

                            /*点击对应的标签后更改当前页面为标签所带的链接*/
                            $scope.onClickTab = function (tab) {
                                $scope.currentTab = tab.url;
                            };
                            $scope.isActiveTab = function(tabUrl) {
                                return tabUrl == $scope.currentTab;
                            }; 
                        })
                    });
                });
            }}
        );
        

        /*获取当前用户的绑定上传者信息*/
        $scope.accountBind = [];
        $scope.noBind = false;
        $scope.getAccountBind = function(){
            if($rootScope.userGroupID!==2){
                return false;
            }
            $http.get("../index.php/user_H5/getAccountBind")
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data['accountBind'] && data['accountBind'].length>0){
                        $scope.accountBind = data['accountBind'];
                    }else{
                        $scope.noBind = true;
                    }
                })
                .error(function(){
                    $rootScope.checkLogStatus();
                });
        };

        /*获取最新的1000条数据*/
        $scope.dataFinished = true;
        $scope.getDefaultCase = function(){
            /*点击之后禁用按钮*/
            $scope.dataFinished = false;
            $(".time-period input").addClass("disabled");
            $scope.filterKey = {};
            $scope.filterItems['timePeriod'] = "all";
            $scope.jsChanged = true;
            var promise = $http.get("../index.php/user_H5/getCaseData")
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data['code']==1){
                        $rootScope.caseDatas = $rootScope.shapeData(data['caseRecord']);
                        $scope.cutData($rootScope.caseDatas);
                        if($rootScope.caseDatas.length>0){
                            $scope.filterItems['startDate'] = new Date($rootScope.caseDatas[$rootScope.caseDatas.length-1]['SubmitTime']);
                            $scope.jsChanged = true;
                        }
                    }else{
                        $rootScope.caseDatas = [];
                    }
                    $(".time-period input").removeClass("disabled");
                    $scope.dataFinished = true;
                }).error(function(){
                    $rootScope.checkLogStatus();
            });
            return promise;
        };

        /*当条件改变时获取新的病例记录*/
        $scope.getCaseData = function(paramObj){
            $(".time-period input").addClass("disabled");
            $scope.filterKey = {};
            var promise = $http({
                method : 'POST',
                url    : "../index.php/user_H5/getCaseData",
                data   : $.param(paramObj),
                headers:{'Content-Type':'application/x-www-form-urlencoded',"charset":"utf-8"}
            }).success(function(data){
                if(data=="offline"){
                    $location.path("/login");
                    return false;
                }
                $rootScope.caseDatas = $rootScope.shapeData((data['code']==1)?data['caseRecord']:[]);
                $(".time-period input").removeClass("disabled");
            }).error(function(){
                $rootScope.checkLogStatus();
            })
            return promise;
        };

        /*定义默认的数据类型为Holter*/
        $scope.dataType = "Holter";

        /*定义默认的信息筛选起始时间：起点是2011/01/01，终止时间是当天*/
        $scope.filterItems = {
            startDate  : new Date("2011/01/01"),
            endDate    : new Date(),
            start      : new Date("2011/01/01").getTime(),
            end        : new Date().getTime(),
            timePeriod :'all'
        };

        /*设置分页参数: 这个页面共用到4个分页，相互独立*/

        /*我的分析页面中 正在分析 部分的分页符*/
        $scope.pagerConfig1 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*我的分析页面中 正在分析待确定 部分的分页符*/
        $scope.pagerConfig2 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*我的分析 页面中 报告已经确定 部分的分页符*/
        $scope.pagerConfig3 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        $scope.pagerConfig4 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*将病例数据分为三类：正在分析、分析完毕待确定、报告已确定*/
        $scope.cutData = function(datas){

            $scope.uploadedDatas   = [];
            $scope.diagnosingDatas = [];
            $scope.diagnosedDatas  = [];
            $scope.retrievedDatas  = [];
            if(datas.length==0){
                $scope.pageUploadedDatas   = [];
                $scope.pageDiagnosingDatas = [];
                $scope.pageDiagnosedDatas  = [];
                $scope.pageRetrievedDatas  = [];
            }else{
                datas.forEach(function(val){
                    switch(parseInt(val['Status'])){
                        case 1: $scope.uploadedDatas.push(val); break;
                        case 2: $scope.diagnosingDatas.push(val); break;
                        case 3: $scope.diagnosedDatas.push(val); break;
                        case 4: $scope.retrievedDatas.push(val); break;
                        default:$scope.uploadedDatas.push(val);
                    }
                });
            }

            /*根据每类记录的总条数 改变 对应页码参数中的总数*/
            $scope.pagerConfig1.totalItems = $scope.uploadedDatas.length;
            $scope.pagerConfig2.totalItems = $scope.diagnosingDatas.length;
            $scope.pagerConfig3.totalItems = $scope.diagnosedDatas.length;
            $scope.pagerConfig4.totalItems = $scope.retrievedDatas.length;

            /*初始归零*/
            $scope.pagerConfig1.start = $scope.pagerConfig2.start = $scope.pagerConfig3.start = $scope.pagerConfig3.start = 0;

            /*根据分页参数截取相应的数据放到页面上*/
            $scope.pageUploadedDatas   = $scope.uploadedDatas.slice(0,$scope.pagerConfig1.itemsPerPage);
            $scope.pageDiagnosingDatas = $scope.diagnosingDatas.slice(0,$scope.pagerConfig2.itemsPerPage);
            $scope.pageDiagnosedDatas  = $scope.diagnosedDatas.slice(0,$scope.pagerConfig3.itemsPerPage);
            $scope.pageRetrievedDatas  = $scope.retrievedDatas.slice(0,$scope.pagerConfig4.itemsPerPage);

            /*根据页码参数生成对应的页码*/
            $scope.pagerCodes1 = $rootScope.bornPagerCode($scope.pagerConfig1);
            $scope.pagerCodes2 = $rootScope.bornPagerCode($scope.pagerConfig2);
            $scope.pagerCodes3 = $rootScope.bornPagerCode($scope.pagerConfig3);
            $scope.pagerCodes4 = $rootScope.bornPagerCode($scope.pagerConfig4);
        };

        $scope.$watch('pagerConfig1',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageUploadedDatas = $scope.uploadedDatas.slice($scope.pagerConfig1.start, $scope.pagerConfig1.start+parseInt($scope.pagerConfig1.itemsPerPage) );
            }
        },true);

        $scope.$watch('pagerConfig2',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageDiagnosingDatas = $scope.diagnosingDatas.slice($scope.pagerConfig2.start, $scope.pagerConfig2.start+parseInt($scope.pagerConfig2.itemsPerPage) );
            }
        },true);

        $scope.$watch('pagerConfig3',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageDiagnosedDatas = $scope.diagnosedDatas.slice($scope.pagerConfig3.start, $scope.pagerConfig3.start+parseInt($scope.pagerConfig3.itemsPerPage) );
            }
        },true);

        $scope.$watch('pagerConfig4',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageRetrievedDatas = $scope.retrievedDatas.slice($scope.pagerConfig4.start, $scope.pagerConfig4.start+parseInt($scope.pagerConfig4.itemsPerPage) );
            }
        },true);

        $rootScope.caseDatas = [];
        $scope.$watch('caseDatas',function(newVal,oldVal){
            if(newVal.length>=0){
                $scope.cutData($rootScope.caseDatas);
            }
        },true);

        /*对数据进行深度排序*/
        $scope.sort = function(area,dataArr,event,type){

            var t = $(event.target);

            if(t.attr("class").indexOf("down")!=-1){
                t.removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-up");
                dataArr = $rootScope.sortDataByKey(dataArr,type,-1);
            }else{
                t.removeClass("glyphicon-chevron-up").addClass("glyphicon-chevron-down");
                dataArr = $rootScope.sortDataByKey(dataArr,type,1);
            }
            if(area==0){
                $scope.cutData(dataArr);
            }else if(area==1){
                $rootScope.applyFilteredData(dataArr);
            }
        };

        $scope.filterKey = {};
        $scope.viewFilter = function(dataArr){
            var tempArr = [];
            var isFiltered = false;
            for(key in $scope.filterKey){
                var val = $.trim($scope.filterKey[key]);
                if(val){
                    tempArr = [];
                   dataArr.forEach(function(dataObj){
                        if(dataObj[key].indexOf(val)!=-1){
                            tempArr.push(dataObj);
                        }
                    });
                    dataArr = tempArr;
                    isFiltered = true;
                }
            }
            isFiltered?$scope.cutData(dataArr):$scope.cutData($rootScope.caseDatas);
        };

        /*监控时间区间radio按钮，当发生改变时则更改filterItems对象中的数据*/
        $scope.$watch('filterItems.timePeriod',function(newTime,oldTime){
            if(newTime!=oldTime){
                $scope.filterItems['startDate'] = new Date($rootScope.datePeriodOfWord[newTime]['start']);
                $scope.filterItems['endDate']   = new Date($rootScope.datePeriodOfWord[newTime]['end']);
                $scope.jsChanged = false;
            }
        });

        $scope.$watch('filterItems.startDate',function(newTime,oldTime){
            if(newTime != oldTime && $scope.filterItems['startDate']){
                $scope.filterItems['start'] = $scope.filterItems['startDate'].getTime();
            }
        });

        $scope.$watch('filterItems.endDate',function(newTime,oldTime){
            if(newTime != oldTime && $scope.filterItems['endDate']){
                $scope.filterItems['end']   = $scope.filterItems['endDate'].getTime();
            }
        });

        /*深度监听 信息筛选对象，当发生变化时，更按要求对数据进行过滤*/
        $scope.$watch('filterItems',function(newTime,oldTime){
            if($scope.jsChanged){
                $scope.jsChanged = false;
                return false;
            }
            if(JSON.stringify(newTime) != JSON.stringify(oldTime)){
                if($scope.filterItems['start'] < $scope.filterItems['end']){
                    var promise2 = $scope.getCaseData($scope.filterItems);
                    promise2.then(function(){
                        $scope.cutData($rootScope.caseDatas);
                    })
                }
            }
        },true);

    }]);

//声明控制器adminCtrl，功能是控制 管理员 + 组 的页面以及数据逻辑
app.controller('adminCtrl',['$scope','$http','$routeParams','$rootScope','$location',
    function($scope,$http,$routeParams,$rootScope,$location){

        /*检查用户登录状态，并且获取用户信息*/
        var promise = $rootScope.checkLogStatus();

        /* 检查完登录状态之后 再获取相对应的数据*/
        promise.then(
            function(){
                if($rootScope.isLogged){
                    var pro = $rootScope.getUserInfo();
                    pro.then(function(){
                        /*检查URL和用户组别是否相配*/
                        $rootScope.checkGroupUrl();

                        if($rootScope.userGroupID==3||$rootScope.userGroupID==4){
                            $scope.tabs = [
                                {title: $rootScope.langSet['title_manage_user'],     url: 'tpl/basePage/accountList.html'},
                                {title: $rootScope.langSet['title_manage_group'],    url: 'tpl/basePage/groupList.html'},
                                {title: $rootScope.userGroupID==3?$rootScope.langSet['title_manage_rec_data']:$rootScope.langSet['title_manage_report'], url: 'tpl/basePage/caseSearchGroup.html'},
                                {title: $rootScope.langSet['title_my_profile'],      url: 'tpl/basePage/profile.html'}
                            ];
                        }else if($rootScope.userGroupID==6||$rootScope.userGroupID==7){
                            $scope.tabs = [
                                {title: $rootScope.langSet['title_manage_user'],     url: 'tpl/basePage/accountList.html'},
                                {title: $rootScope.userGroupID==7?$rootScope.langSet['title_manage_rec_data']:$rootScope.langSet['title_search_aly_report'], url: 'tpl/basePage/caseSearchGroup.html'},
                                {title: $rootScope.langSet['title_my_profile'],      url: 'tpl/basePage/profile.html'}
                            ];
                        }

                        /*如果是分析管理员，需要获取本医院下所有采集员的信息列表*/
                        $scope.getAllOptList();


                        /*默认显示第一个标签及子页面*/
                        $scope.currentTab = $scope.tabs[0]['url'];

                        $scope.onClickTab = function (tab) {
                            $scope.currentTab = tab.url;
                        };
                        $scope.isActiveTab = function(tabUrl) {
                            return tabUrl == $scope.currentTab;
                        };

                        /*获取相应医院下的分析员账号列表*/
                        $scope.getAccountData();
                        $rootScope.userGroupID<5 && $scope.getGroupData();
                        $rootScope.getHospitalList();

                        /*初始化新用户的信息*/
                        $scope.newAccount = {
                            hospitalName : $rootScope.userInfo['HospitalName'],
                            hospitalID   : $rootScope.userInfo['HospitalID'],
                            groupID      : $rootScope.userGroupID-2,
                            status       : ""
                        };

                        /*初始化新用户组的信息*/
                        $scope.newGroupAccount = {
                            hospitalName : $rootScope.userInfo['HospitalName'],
                            hospitalID   : $rootScope.userInfo['HospitalID'],
                            groupID      : $rootScope.userGroupID==3?7:6,
                            status       : ""
                        };
                    })
                }
            }
        );

        /*当是分析管理员时需要获取全部采集员的列表, 用于创建新用户或者编辑分析员时的绑定用户场景*/
        $scope.optList = [];
        $scope.getAllOptList = function(){
            $http.get("../index.php/user_H5/getAllOptList")
            .success(function(data){
                if(data=="offline"){
                    $location.path("/login");
                    return false;
                }
                if(data['code']==1){
                    $scope.optList = data['optList'];
                    $scope.defaultConfig['optList'] = $scope.optList;
                }
            })
            .error(function(){
                $rootScope.checkLogStatus();
            });
        };


        /*设置默认的defaultConfig,用在多列选择栏中*/
        $scope.defaultConfig = {};
        $scope.defaultConfig['selectedOptions'] = $rootScope.langSet.no_limit;
        $scope.defaultConfig['selectedArr']     = [];
        $scope.defaultConfig['selectedArrID']   = [];
        $scope.defaultConfig['isShowed']        = false;

        $scope.$watch("defaultConfig['selectedArrID']",function(newVal,oldVal){
          newVal!=oldVal && ($scope.newAccount['userBind'] = newVal) ;
        },true);


        /*---获取用户子账号数据：包括分析员（采集员）数据 */
        $scope.accountDatas = [];
        $scope.getAccountData = function(){
            $http.get("../index.php/user_H5/getAccountData")
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data['code']=="1"){
                        $scope.accountDatas = data['accountList'];
                        $scope.bornAccountNameList($scope.accountDatas);
                        $scope.applyDatasToPage0($scope.accountDatas);
                    }
                }).error(function(){
                    $rootScope.checkLogStatus();
                });
        };

        /*---获取用户子组账号数据：包括分析组（采集组）数据 */
        $scope.groupAccountDatas = [];
        $scope.getGroupData = function(){
            $http.get("../index.php/user_H5/getGroupData")
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data['code']=="1"){
                        $scope.groupAccountDatas = data['groupList'];
                        $scope.applyDatasToPage1($scope.groupAccountDatas);
                    }
                }).error(function(){
                    $rootScope.checkLogStatus();
                });
        };

        /*设置分页参数: 这个页面共用到4个分页，相互独立*/
        /*分析用户管理页面的分页符*/
        $scope.pagerConfig1 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*分析用户组管理页面的分页符*/
        $scope.pagerConfig2 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*定义applyDatasToPage函数，将对应的数据安排到用户管理页面中
         * 1.根据数据的条数更改分页码的总数
         * 2.根据分页码参数生成分页码信息pagerCodes1
         * 3.根据分页码参数生成当前页面的记录信息
         * */
        //将数据应用到用户管理页面
        $scope.applyDatasToPage0 = function(datas){
            if(!datas) {
                datas = [];
                return false;
            }
            $scope.pagerConfig1.totalItems = datas.length;
            $scope.pagerCodes1      = $rootScope.bornPagerCode($scope.pagerConfig1);
            $scope.pageAccountDatas = $scope.accountDatas.slice($scope.pagerConfig1.start,$scope.pagerConfig1.start+parseInt($scope.pagerConfig1.itemsPerPage));
        };
        //将数据加载到用户组管理页面
        $scope.applyDatasToPage1 = function(datas){
            if(!datas) {
                datas = [];
                return false;
            }
            $scope.pagerConfig2.totalItems = datas.length;
            $scope.pagerCodes2 = $rootScope.bornPagerCode($scope.pagerConfig2);
            $scope.pageGroupAccountDatas = $scope.groupAccountDatas.slice($scope.pagerConfig2.start,$scope.pagerConfig2.start+parseInt($scope.pagerConfig2.itemsPerPage));
        };

        /*采用深度监听，监听pagerConfig1对象，当config对象发生改变时，数据也会相应地变化*/
        $scope.$watch('pagerConfig1',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageAccountDatas = $scope.accountDatas.slice($scope.pagerConfig1.start,$scope.pagerConfig1.start+parseInt($scope.pagerConfig1.itemsPerPage));
            }
        },true);

        /*采用深度监听，监听pagerConfig2对象，当config对象发生改变时，数据也会相应地变化*/
        $scope.$watch('pagerConfig2',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageGroupAccountDatas = $scope.groupAccountDatas.slice($scope.pagerConfig2.start,$scope.pagerConfig2.start+parseInt($scope.pagerConfig2.itemsPerPage));
            }
        },true);

        /*对数据进行排序*/
        $scope.sort = function(area,dataArr,event,type){

            var t = $(event.target);

            if(t.attr("class").indexOf("down")!=-1){
                t.removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-up");
                dataArr = $rootScope.sortDataByKey(dataArr,type,-1);
            }else{
                t.removeClass("glyphicon-chevron-up").addClass("glyphicon-chevron-down");
                dataArr = $rootScope.sortDataByKey(dataArr,type,1);
            }
            if(area==0){
                $scope.cutData(dataArr);
            }else if(area==1){
                $rootScope.applyFilteredData(dataArr);
            }
        };

        /*定义函数：档案编辑重置*/
        $scope.resetEditAccountProfile = function(accountData){
            accountData = JSON.parse(JSON.stringify(accountData));
            delete(accountData['$$hashKey']);
            delete(accountData['Pwd']);
            $scope.editAccount = accountData;
            $scope.editAccount['Age'] = parseInt(accountData['Age']);
            $scope.editAccount['Status'] = "";
        };

        /*定义函数：打算修改子用户档案信息*/
        $scope.toEditAccountProfile = function(accountData){
            $rootScope.singleAccountEditing = true;
            $scope.resetEditAccountProfile(accountData);

            $("input[data-value]").prop("checked",false);
            $scope.defaultConfig.selectedArr     = [];
            $scope.defaultConfig.selectedArrID   = [];
            $scope.defaultConfig.isShowed        = false;
            $scope.defaultConfig.selectedOptions = $rootScope.langSet.no_limit;
            if($scope.singleAccountBind && $scope.singleAccountBind.length>0){
                $scope.singleAccountBind.forEach(function(bindOpt){
                    $scope.defaultConfig.selectedArr.push(bindOpt['Name']);
                    $scope.defaultConfig.selectedArrID.push(bindOpt['ID']);
                    $("input[data-value="+bindOpt['ID']+"]").prop("checked",true);
                });
                $scope.defaultConfig.selectedOptions = $scope.defaultConfig.selectedArr.toString();
            }
        };

        /*定义函数：取消修改子用户档案信息*/
        $scope.cancelEditAccountProfile = function(){
            $rootScope.singleAccountEditing = false;
        };

        /*定义函数：提交每个子用户档案编辑的数据*/
        $scope.toUpdateAccountProfile = function(){
            if($scope.editAccount['NewPwd']!=$scope.editAccount['NewPwd2']){
                $scope.editAccount['Status'] = $rootScope.langSet.password_must_same;
                return false;
            }
            $scope.editAccount['NewPwd'] && ($scope.editAccount['NewPwd'] = $rootScope.MD5($scope.editAccount['NewPwd']));
            $scope.editAccount['NewPwd2'] && ($scope.editAccount['NewPwd2'] = $rootScope.MD5($scope.editAccount['NewPwd2']));

            $rootScope.userGroupID==4 && $scope.editAccount['GroupID']==2 && ($scope.editAccount['BindAccount'] = $scope.defaultConfig['selectedArrID']);

            $http({
                method:'POST',
                data:$.param($scope.editAccount),
                url:'../index.php/user_H5/editAccountProfile',
                headers: {'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
            })
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    $scope.editAccount['NewPwd']  = $scope.editAccount['NewPwd2'] = "";
                    /*如果修改成功，则关闭对话框，并且退出本次登录要求重新登录*/
                    if(data['code']==1){
                        $scope.editAccount['Status']  = $rootScope.langSet.update_success;
                        ($scope.editAccount['GroupID']<3 && $scope.getAccountData()) || ($scope.editAccount['GroupID']>5 && $scope.getGroupData());
                    }else if(data['code']==-2) {
                        $scope.editAccount['Status']  = $rootScope.langSet.duplicated_account;
                    }else{
                        $scope.editAccount['Status']  = $rootScope.langSet.update_failed;
                    }
                })
                .error(function(){
                    $rootScope.checkLogStatus();
                });
        };

        /*定义函数：点击锁定或者激活，更改相应账号的状态*/
        $scope.toggleAccountStatus = function(id,status){
            $http.get('../index.php/user_H5/toggleAccountStatus/'+id+'/'+status)
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    data['code']==1 && $scope.getAccountData();
                }).error(function(){
                    $rootScope.checkLogStatus();
                 });
        };

        $scope.addNewAccount = function(){
            $scope.defaultConfig['selectedOptions'] = $rootScope.langSet.no_limit;
            $scope.defaultConfig['selectedArr']     = [];
            $scope.defaultConfig['selectedArrID']   = [];
            $("input[data-value]").prop("checked",false);
        };

        /*定义函数：添加新用户 toAddNewAccount
        * 向数据表bene_user中插入新数据
        * 1.首先要验证数据的正确性：关键信息不可缺失、密码要PWD加密，帐户名不可重复
        * 2.返回新增用户的结果：成功 还是 失败
        * 3.如果新增用户成功，则更新当前用户列表页面
        * */
        $scope.toAddNewAccount = function(dataObject){

            if(dataObject['pwd'] != dataObject['pwd2']){
                $scope.newAccount['status'] = $rootScope.langSet['password_must_same'];
                return false;
            }

            if(dataObject['pwd'] && dataObject['pwd'].length>3){
                dataObject['pwd']   = $rootScope.MD5(dataObject['pwd']);
                dataObject['pwd2']  = $rootScope.MD5(dataObject['pwd2']);
            }

            if(dataObject.hospitalID && dataObject.groupID && dataObject.account && dataObject.pwd && dataObject.pwd == dataObject.pwd2){
                $http({
                    method : "POST",
                    data   : $.param(dataObject),
                    url    : "../index.php/user_H5/addNewAccount",
                    headers: {'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
                })
                .success(
                    function(data){
                        if(data['code']==1){
                            if(dataObject['groupID']<=2){
                                $scope.newAccount['status'] = $rootScope.langSet.update_success;
                                $scope.defaultConfig['selectedOptions'] = $rootScope.langSet.no_limit;
                                $scope.defaultConfig['selectedArr']     = [];
                                $scope.getAccountData();
                            }else{
                                $scope.newGroupAccount['status'] = $rootScope.langSet.update_success;
                                $scope.getGroupData();
                            }
                        }else{
                            if(dataObject['groupID']<=2){
                                $scope.newAccount['status'] = $rootScope.langSet.update_failed;
                            }else{
                                $scope.newGroupAccount['status'] = $rootScope.langSet.update_failed;
                            }
                        }

                        if(dataObject['groupID']<=2) {
                            $scope.newAccount['pwd']  = $scope.newAccount['pwd2'] = "";
                        }else{
                            $scope.newGroupAccount['pwd']  = $scope.newGroupAccount['pwd2'] = "";
                        }
                    }
                ).error(function(){
                    $rootScope.checkLogStatus();
                });
            }
        };

        /*检查账号是否已经存在*/
        $scope.checkRepeat = function(account,groupID){
            if(!account || !groupID){
                return false;
            }
            $http.get("../index.php/user_H5/checkAccountRepeat/"+account)
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data==1){
                        if(groupID<=2){
                            $scope.accountRepeat = true;
                            $scope.newAccount['status']      = $rootScope.langSet.duplicated_account;
                        }else{
                            $scope.groupRepeat = true;
                            $scope.newGroupAccount['status'] = $rootScope.langSet.duplicated_account;
                        }
                    }else{
                        $scope.accountRepeat = $scope.groupRepeat = false;
                        $scope.newGroupAccount['status'] = "";
                        $scope.newAccount['status']      = "";
                    }
                }).error(function(){
                     $rootScope.checkLogStatus();
                });
        };

        /*定义函数：生成普通用户姓名列表bornAccountNameList
        * 将accountDatas对象遍历，获取相应的名称，保存在对象中        *
        * */
        $scope.bornAccountNameList = function(dataArray){
            $scope.accountNameList = {};
            if(dataArray && dataArray.length>0){
                dataArray.forEach(function(val){
                    $scope.accountNameList[val.ID] = val.Name;
                })
            }
        };

        /*重置新增用户表*/
        $scope.toResetNewAccount = function(){
            $scope.newAccount = {
                hospitalName : $rootScope.userInfo['HospitalName'],
                hospitalID   : $rootScope.userInfo['HospitalID'],
                groupID      : $rootScope.userGroupID-2,
                status       : ""
            };
        };

        /*重置新增用户组表*/
        $scope.toResetNewGroupAccount = function(){
            $scope.newGroupAccount = {
                hospitalName : $rootScope.userInfo['HospitalName'],
                hospitalID   : $rootScope.userInfo['HospitalID'],
                groupID      : $rootScope.userGroupID==3?7:6,
                status       : ""
            };
        };

        /*定义函数：编辑用户组-
        1. 根据用户组的groupID找出下面所有组员的列表，采集组对应所有的采集员，分析组对应所有的分析员 -- 已经有了，不需要重复做
        2. 根据用户组的ID访问bene_user_group表，获取已经绑定的成员列表
        3. 根据用户组的ID访问bene_user表，获取用户组的信息
        4. 将以上数据信息渲染到页面
        5. 当提交数据时，将相应的数据写入到bene_user_group表中
        6. 返回数据写入结果
        */
        $scope.bindGroup = {};        

        /*定义函数：点击编辑分析组后显示相应的页面*/
        $scope.toEditGroupUser = function(groupAccountData){
            $scope.bindGroup.status="";                        

            if(typeof(groupAccountData)!=="object"){
                return false;
            }

            if(groupAccountData.ID && groupAccountData.GroupID){

                $scope.bindGroup = groupAccountData;

                $http.get("../index.php/user_H5/getGroupUserList/" + groupAccountData.ID)
                    .success(function(data){
                        (data == "offline") && $rootScope.exit();
                        $scope.bindGroup.userList = [];
                        if(data['code']==1){                           

                            if(data['userList'] && data['userList'].length>0){                                                               
                                data['userList'].forEach(function(val){
                                    $scope.bindGroup.userList.push(val["UserID"]);                                    
                                });
                            }
                            
                            $scope.accountDatas.forEach(function(accountData){
                                accountData.checked = ($scope.bindGroup.userList.indexOf(accountData.ID)!=-1);                                
                            })                                                       
                        }
                    }).error(function(){
                        $rootScope.checkLogStatus();
                    });
            }
        };

        /*监听accountDatas，如果有改变，则重置编辑状态为空*/
        $scope.$watch("accountDatas",function(newObj,oldObj){
            if(newObj!=oldObj){
                $scope.bindGroup.status = "";
            }
        },true)

        /*定义函数：当点击保存后将用户的操作保存到数据库中*/
        $scope.toEditGroup = function(dataObject){ 
            $scope.bindGroup.member=[];          
            $scope.accountDatas.forEach(function(accountData){
                if(accountData.checked){
                    $scope.bindGroup.member.push(accountData.ID);
                }
            })

            var data = {
                id:dataObject['ID'],
                member:$scope.bindGroup.member
            };

            $http({
                method : "POST",
                data   : $.param(data),
                url    : "../index.php/user_H5/updateGroupBind",
                headers: {'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
            })
            .success(function(data){
                if(data=="offline"){
                    $location.path("/login");
                    return false;
                }
                $scope.bindGroup.status = ((data['code']==1)?$rootScope.langSet['edit_group_success']:$rootScope.langSet['edit_group_failed']);
            })
            .error(function(){
                $rootScope.checkLogStatus();
            });
        };

        /*准备将操作组绑定到分析组：获取需要绑定的信息（分析组，及各组成员），以及已经绑定的信息，并渲染到页面中*/
        $scope.bindAlyGroup = {};
        $scope.toBindAlyGroup = function(userInfo){
            $scope.bindAlyGroup['status'] = "";
            if(userInfo.GroupID=="7"){
                $http.get("../index.php/user_H5/getGroupData")
                    .success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        if(data['code']==1){
                            $scope.alyGroupDatas = data['groupList'];
                            $scope.selectedGroup = data['selectedGroup'];

                            $scope.selectedGroupID = [];
                            if($scope.selectedGroup && $scope.selectedGroup.length>0){
                                $scope.selectedGroup.forEach(function(val){
                                    $scope.selectedGroupID.push(val.UserID_A);    
                                })
                            }

                            $scope.alyGroupDatas.forEach(function(dataObj){
                                dataObj.checked = ($scope.selectedGroupID.indexOf(dataObj.ID)!=-1);                                
                            })
                        }
                    }).error(function(data){
                        $rootScope.checkLogStatus()
                })
            }
        };

        /*更新操作组 绑定的分析组信息*/
        $scope.updateBindAlyGroup = function(){
            $scope.bindAlyGroup['optGroupAccountID'] = $rootScope.userInfo['ID'];
            $scope.bindAlyGroup['checkedAlyGroupAccountID'] = [];

            $scope.alyGroupDatas.forEach(function(dataObj){
                if(dataObj.checked){
                    $scope.bindAlyGroup['checkedAlyGroupAccountID'].push(dataObj.ID);
                }
            })

            $http({
                method:"POST",
                data: $.param($scope.bindAlyGroup),
                url:"../index.php/user_H5/updateBindAlyGroup",
                headers:{'Content-Type': 'application/x-www-form-urlencoded',"charset":"utf-8"}
            }).success(function(data){
                if(data=="offline"){
                    $location.path("/login");
                    return false;
                }
                $scope.bindAlyGroup['status'] = (data? $rootScope.langSet['update_success'] : $rootScope.langSet['update_failed']);

                $scope.$watch('alyGroupDatas',function(newObj,oldObj){
                    if(newObj!=oldObj){
                        $scope.bindAlyGroup['status'] = "";
                    }
                },true)

            }).error(function(){
                $rootScope.checkLogStatus();
            })
        }

    }]);


//声明控制器superCtrl，功能是管理用户档案
app.controller('superCtrl',['$scope','$http','$routeParams','$rootScope','$location',
    function($scope,$http,$routeParams,$rootScope,$location){

        /*检查用户登录状态，并且获取用户信息*/
        var promise = $rootScope.checkLogStatus();

        /*检查完登录状态之后 再获取相对应的数据, 并配置相应的页面*/
        promise.then(
            function(){
                if($rootScope.isLogged){
                    var pro = $rootScope.getUserInfo();
                    pro.then(function(){
                        /*检查URL和用户组别是否相配*/
                        $rootScope.checkGroupUrl();

                        /*获取当前在线用户列表*/
                        $scope.getOnlineUserList();

                        /*获取当前所有医院信息列表*/
                        $scope.getHospitalList();

                        /*根据账号信息配置相应的页面*/
                        $scope.tabs = [
                            {title: $rootScope.langSet['title_view_online_user'], url: 'tpl/basePage/accountList.html'},
                            {title: $rootScope.langSet['title_manage_hospital'],  url: 'tpl/basePage/hospitalList.html'},
                            {title: $rootScope.langSet['title_my_profile'],       url: 'tpl/basePage/profile.html'}
                        ];

                        /*默认显示第一个标签及子页面*/
                        $scope.currentTab = $scope.tabs[0]['url'];

                        $scope.onClickTab = function (tab) {
                            $scope.currentTab = tab.url;
                        };
                        $scope.isActiveTab = function(tabUrl) {
                            return tabUrl == $scope.currentTab;
                        };
                    })
                }
            }
        );

        /*获取当前在线用户列表*/
        $scope.accountDatas = [];
        $scope.getOnlineUserList = function(){
            $http.get("../index.php/user_H5/getOnlineUserList")
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data['code']==1){
                        $scope.accountDatas = data['userList'];
                        $scope.pagerConfig0.totalItems = $scope.accountDatas.length;
                        $scope.pagerCodes0 = $rootScope.bornPagerCode($scope.pagerConfig0);
                        $scope.applyDatasToPage0($scope.accountDatas);
                    }
                })
                .error(function(){
                    $rootScope.checkLogStatus();
                });
        };

        /*定义函数：清除Action记录 todo list*/
        $scope.clearActionRecord = function(){
        };

        /*获取当前所有医院信息列表 -- 超级管理员*/
        $scope.hospitalDatas = [];
        $scope.getHospitalList = function(flag){
            $http.get("../index.php/user_H5/getAllHospitalList")
                .success(function(data){
                    (data == "offline") && $rootScope.exit();
                    if(data['code']==1){
                        $scope.hospitalDatas = data['hospitalList'];
                        $scope.pagerConfig1.totalItems = $scope.hospitalDatas.length;
                        $scope.pagerCodes1 = $rootScope.bornPagerCode($scope.pagerConfig1);
                        $scope.applyDatasToPage1($scope.hospitalDatas);
                    }
                    $scope.addChartJS("js/echarts.min.js");
                })
                .error(function(){
                    $rootScope.checkLogStatus();
                });
        };

        /*加载echart.js文件：当获取到医院数据之后*/
        $scope.addChartJS = function(url){
            var body = document.getElementsByTagName("body")[0];
            var s = document.createElement("script");
            s.src = url;
            body.appendChild(s);
        };

        /*将数据应用到相应的页面：数据切割*/
        $scope.applyDatasToPage0 = function(data){
            $scope.accountDatas = data.slice($scope.pagerConfig0.start,$scope.pagerConfig0.start+$scope.pagerConfig0.itemsPerPage);
        };

        $scope.applyDatasToPage1 = function(data){
            $scope.pageHospitalDatas = data.slice($scope.pagerConfig1.start,$scope.pagerConfig1.start+$scope.pagerConfig1.itemsPerPage);
        };

        /*在线用户页面的分页符*/
        $scope.pagerConfig0 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*医院管理页面的分页符*/
        $scope.pagerConfig1 = {
            itemsPerPage:10,
            totalItems:0,
            start:0,
            totalPages:Math.ceil(this.totalItems/this.itemsPerPage)
        };

        /*采用深度监听，监听pagerConfig0对象，当config对象发生改变时，数据也会相应地变化*/
        $scope.$watch('pagerConfig0',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageAccountDatas = $scope.accountDatas.slice($scope.pagerConfig0.start,$scope.pagerConfig0.start+parseInt($scope.pagerConfig0.itemsPerPage));
            }
        },true);

        /*采用深度监听，监听pagerConfig1对象，当config对象发生改变时，数据也会相应地变化*/
        $scope.$watch('pagerConfig1',function(newVal,oldVal){
            if(newVal!=oldVal){
                $scope.pageHospitalDatas = $scope.hospitalDatas.slice($scope.pagerConfig0.start,$scope.pagerConfig0.start+parseInt($scope.pagerConfig0.itemsPerPage));
            }
        },true);

        /*定义图表样式*/
        $scope.chartType ={
            bar:"bar",
            line:"line",
            pie:"pie"
        };

        /*定义图表时间单位*/
        $scope.chartPeriod = {
            day:"day",
            week:"week",
            month:"month",
            season:"season",
            halfYear:"halfYear",
            year:"year"
        };

        /*显示医院详细信息*/
        $scope.showHospitalInfo = function(hospitalData){
            $scope.singleHospitalInfo = hospitalData;

            /*设定默认的图表显示样式和时间单位*/
            $scope.chartConfig = {
                type:"bar",
                period:"day"
            };

            var leftChart        = echarts.init(document.getElementById("leftCanvas"));
            var rightTopChart    = echarts.init(document.getElementById("rightTopCanvas"));
            var rightBottomChart = echarts.init(document.getElementById("rightBottomCanvas"));

            var barOption = {
                title:{
                    text:$rootScope.langSet.case_statistic_label
                },

                tooltip:{},
                legend: {
                    x: 'right',
                    data: [$rootScope.langSet.operated_count_label, $rootScope.langSet.uploaded_count_label]
                },
                calculable : true,
                grid: {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'shadow',
                            label: {
                                show: true
                            }
                        }
                    }
                },
                xAxis:{
                    data:[]
                },
                yAxis:{},
                dataZoom:[
                    {
                        type:'slider',
                        xAxisIndex:0,
                        start:0,
                        end:100
                    },
                    {
                        type:"inside",
                        xAxisIndex:0,
                        start:0,
                        end:100
                    },
                    {
                        type:"slider",
                        yAxisIndex:0,
                        start:0,
                        end:100
                    },
                    {
                        type:"inside",
                        yAxisIndex:0,
                        start:0,
                        end:100
                    }
                ],
                series:[
                    { name:$rootScope.langSet.operated_count_label, type:"bar"},
                    { name:$rootScope.langSet.uploaded_count_label, type:"bar"}
                ]
            };

            var pieOption = {
                title:{
                    text:$rootScope.langSet.uploaded_percent_label,
                    x:"center"
                },
                tooltip:{
                    trigger:"item",
                    formatter: "{b} : {c} ({d}%)"
                },
                calculable:true,
                series : [
                    {
                        name: '分析比例',
                        type: 'pie',
                        radius: '45%'
                    }
                ]
            };

            leftChart.setOption(barOption);
            rightTopChart.setOption(pieOption);
            rightBottomChart.setOption(pieOption);

            leftChart.showLoading();
            rightTopChart.showLoading();
            rightBottomChart.showLoading();

            /*获取chart图表需要的数据*/
            $scope.getChartData = function(configObj){
                $http.get("../index.php/user_H5/getChartData/"+hospitalData.ID+"/"+configObj.period)
                    .success(function(data){
                        if(data=="offline"){
                            $location.path("/login");
                            return false;
                        }
                        /*需要这样的数据格式*/
                        $scope.chartData = data;

                        var collectedSum = 0;
                        var uploadedSum = 0;
                        data['caseRecord'].forEach(function(val){
                           collectedSum += parseInt(val['collected']);
                           uploadedSum += parseInt(val['uploaded']);
                        });

                    leftChart.hideLoading();
                    rightTopChart.hideLoading();
                    rightBottomChart.hideLoading();
                    var dateArr = [], collectedArr = [], uploadedArr = [];
                    if(data && data.caseRecord && data.caseRecord.length){
                        data['caseRecord'].forEach(function(val){
                            dateArr.push(val.date);
                            collectedArr.push(val.collected);
                            uploadedArr.push(val.uploaded);
                        })
                    }

                    var option = {
                        xAxis: {
                            data: dateArr
                        },
                        series: [
                            {type:configObj.type, data:collectedArr},
                            {type:configObj.type, data:uploadedArr}
                        ]
                    };
                    leftChart.setOption(option);
                    rightTopChart.setOption({
                        series:[{
                            data:[
                                {value:uploadedSum, name:$rootScope.langSet.uploaded_label},
                                {value:(collectedSum - uploadedSum), name:$rootScope.langSet.un_uploaded_label}
                            ]
                        }]
                    });
                    rightBottomChart.setOption({
                        series:[{
                            data:[
                                {value:uploadedSum, name:$rootScope.langSet.uploaded_label},
                                {value:(collectedSum - uploadedSum), name:$rootScope.langSet.un_uploaded_label}
                            ]
                        }]
                    });
                }).error(function(){
                    $rootScope.checkLogStatus();
                });
            };

            $scope.getChartData($scope.chartConfig);

            $scope.$watch('chartConfig',function(newVal,oldVal){
                if(newVal != oldVal){
                    $scope.getChartData($scope.chartConfig);
                }
            },true);

        };

    }]);


//配置路由词典，控制页面的跳转
app.config(function ($routeProvider) {

    $routeProvider
        .when('/login',{
            templateUrl:'tpl/login.html',
            controller:'loginCtrl'
        })
        .when('/admin',{
            templateUrl:'tpl/main.html',
            controller:'adminCtrl'
        })
        .when('/group',{
            templateUrl:'tpl/main.html',
            controller:'adminCtrl'
        })
        .when('/normal',{
            templateUrl:'tpl/main.html',
            controller:'normalCtrl'
        })
        .when('/super',{
            templateUrl:'tpl/main.html',
            controller:'superCtrl'
        })
        .otherwise({redirectTo:'/login'})
});
