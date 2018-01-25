/*设置编码格式+创建数据库*/
set names utf8;

/*创建加密狗数据库,并设定编码格式*/
create database bpnp default character set utf8 collate utf8_general_ci;
use bpnp;

/*创建加密狗产品信息表*/
set names utf8;

/*创建用户账号密码表*/
create table useraccount (
  userID smallint(6) auto_increment primary key,
  userAccount varchar(20) unique not null,
  userPWD varchar(20) not null
) default character set utf8 collate utf8_general_ci;

ALTER TABLE userAccount ADD INDEX (userAccount);
ALTER TABLE userAccount ADD UNIQUE (userAccount);

insert into userAccount(userID,userAccount,userPWD) values
( null,'jerry','test00'),
( null,'jerry1','test11')

/*创建用户信息表*/
use bpnp;
set names utf8;
create table userInfo (
  userID smallint(6) auto_increment primary key,
  account varchar(20) unique not null,
  groupID smallint default 1,
  fullName varchar(10) not null,
  hospital varchar(10) not null,
  createTime int(30) not null,
  lastLoginTime int(30),
  totalLoginTimes int(10) default 0,
  loginIP varchar(15)
) default character set utf8 collate utf8_general_ci;

alter table userInfo add index (userAccount);
alter table userInfo add unique (userAccount);

insert into userInfo(account,groupID,fullName,hospital,createTime) values
('jerry','1','陈军伟','杭州百慧虚拟分析','35664558665' );


use bpnp;
set names utf8;
create table caseRecords(
  caseID smallint(10) auto_increment primary key,
  caseCode varchar(20) unique not null,
  patientName varchar(20) not null,
  patientGender smallint(1) default 0,
  caseType varchar(20) default 'holter',
  hospital varchar(32) not null,
  submitTime int(32) not null,
  analyseTime int(32) not null,
  analyseDoctor varchar(20) not null,
  remark varchar(100),
  status smallint(1) default 0
);

alter table caseRecords add index(caseCode);
insert into caseRecords values
(null,'H1612140NDLVU','test0','男','holter','百慧虚拟','566233245','456689955','doctor0','波动明显',0),
(null,'H1612141NDLVU','test1','男','holter','百慧虚拟','566233255','456689955','doctor1','波动明显',1),
(null,'H1612142NDLVU','test2','男','holter','百慧虚拟','566233265','456689955','doctor2','波动明显',2),
(null,'H1612143NDLVU','test3','男','holter','百慧虚拟','566233275','456689955','doctor3','波动明显',0),
(null,'H1612144NDLVU','test4','男','holter','百慧虚拟','566233285','456689955','doctor4','波动明显',1),
(null,'H1612145NDLVU','test5','男','holter','百慧虚拟','566233295','456689955','doctor5','波动明显',2),
(null,'H1612146NDLVU','test6','男','holter','百慧虚拟','566233205','456689955','doctor6','波动明显',0)


insert into caseRecords values
(null,'H1612150NDLVU','text0','男','holter','千慧虚拟','566233245','456689955','dota0','波动明显',0),
(null,'H1612151NDLVU','text1','男','holter','千慧虚拟','566233255','456689955','dota1','波动明显',1),
(null,'H1612152NDLVU','text2','男','holter','千慧虚拟','5662326d5','456689955','dotoa','波动明显',2),
(null,'H1612153NDLVU','text3','男','holter','千慧虚拟','566233275','456689955','dota3','波动明显',0),
(null,'H1612154NDLVU','text4','男','holter','千慧虚拟','566233285','456689955','dota4','波动明显',1),
(null,'H1612155NDLVU','text5','男','holter','千慧虚拟','566233295','456689955','dota5','波动明显',2),
(null,'H1612156NDLVU','text6','男','holter','千慧虚拟','566233205','456689955','dota6','波动明显',0),

(null,'H1612160NDLVU','dext0','男','holter','千慧虚拟','566233245','456689955','Lisa0','波动明显',0),
(null,'H1612161NDLVU','dext1','男','holter','千慧虚拟','566233255','456689955','Lisa1','波动明显',1),
(null,'H1612162NDLVU','dext2','男','holter','千慧虚拟','5662326d5','456689955','Lisaa','波动明显',2),
(null,'H1612163NDLVU','dext3','男','holter','千慧虚拟','566233275','456689955','Lisa3','波动明显',0),
(null,'H1612164NDLVU','dext4','男','holter','千慧虚拟','566233285','456689955','Lisa4','波动明显',1),
(null,'H1612165NDLVU','dext5','男','holter','千慧虚拟','566233295','456689955','Lisa5','波动明显',2),
(null,'H1612166NDLVU','dext6','男','holter','千慧虚拟','566233205','456689955','Lisa6','波动明显',0),
(null,'H1612167NDLVU','dext0','男','holter','千慧虚拟','566233245','456689955','Lisa0','波动明显',0),
(null,'H1612168NDLVU','dext1','男','holter','千慧虚拟','566233255','456689955','Lisa1','波动明显',1),
(null,'H1612169NDLVU','dext2','男','holter','千慧虚拟','5662326d5','456689955','Lisaa','波动明显',2),

(null,'H1617160NDLVU','Jacky0','男','holter','亿慧虚拟','566233245','456689955','Marry0','波动明显',0),
(null,'H1617161NDLVU','Jacky1','男','holter','亿慧虚拟','566233255','456689955','Marry1','波动明显',1),
(null,'H1617162NDLVU','Jacky2','男','holter','亿慧虚拟','5662326d5','456689955','Marrya','波动明显',2),
(null,'H1617163NDLVU','Jacky3','男','holter','亿慧虚拟','566233275','456689955','Marry3','波动明显',0),
(null,'H1617164NDLVU','Jacky4','男','holter','亿慧虚拟','566233285','456689955','Marry4','波动明显',1),
(null,'H1617165NDLVU','Jacky5','男','holter','亿慧虚拟','566233295','456689955','Marry5','波动明显',2),
(null,'H1617166NDLVU','Jacky6','男','holter','亿慧虚拟','566233205','456689955','Marry6','波动明显',0),
(null,'H1617167NDLVU','Jacky0','男','holter','亿慧虚拟','566233245','456689955','Marry0','波动明显',0),
(null,'H1617168NDLVU','Jacky1','男','holter','亿慧虚拟','566233255','456689955','Marry1','波动明显',1),
(null,'H1617169NDLVU','Jacky2','男','holter','亿慧虚拟','5662326d5','456689955','Marrya','波动明显',2),

(null,'H1622160NDLVU','dest0','男','holter','千慧比拟','566233245','456689955','Liysi0','波动明显',0),
(null,'H1622161NDLVU','dest1','男','holter','千慧比拟','566233255','456689955','Liysi1','波动明显',1),
(null,'H1622162NDLVU','dest2','男','holter','千慧比拟','5662326d5','456689955','Liysia','波动明显',2),
(null,'H1622163NDLVU','dest3','男','holter','千慧比拟','566233275','456689955','Liysi3','波动明显',0),
(null,'H1622164NDLVU','dest4','男','holter','千慧比拟','566233285','456689955','Liysi4','波动明显',1),
(null,'H1622165NDLVU','dest5','男','holter','千慧比拟','566233295','456689955','Liysi5','波动明显',2),
(null,'H1622166NDLVU','dest6','男','holter','千慧比拟','566233205','456689955','Liysi6','波动明显',0),
(null,'H1622167NDLVU','dest0','男','holter','千慧比拟','566233245','456689955','Liysi0','波动明显',0),
(null,'H1622168NDLVU','dest1','男','holter','千慧比拟','566233255','456689955','Liysi1','波动明显',1),
(null,'H1622169NDLVU','dest2','男','holter','千慧比拟','5662326d5','456689955','Liysia','波动明显',2),
(null,'H1627160NDLVU','Jasky0','男','holter','亿比虚拟','566233245','456689955','Mysiry0','波动明显',0),
(null,'H1627161NDLVU','Jasky1','男','holter','亿比虚拟','566233255','456689955','Mysiry1','波动明显',1),
(null,'H1627162NDLVU','Jasky2','男','holter','亿比虚拟','5662326d5','456689955','Mysirya','波动明显',2),
(null,'H1627163NDLVU','Jasky3','男','holter','亿比虚拟','566233275','456689955','Mysiry3','波动明显',0),
(null,'H1627164NDLVU','Jasky4','男','holter','亿比虚拟','566233285','456689955','Mysiry4','波动明显',1),
(null,'H1627165NDLVU','Jasky5','男','holter','亿比虚拟','566233295','456689955','Mysiry5','波动明显',2),
(null,'H1627166NDLVU','Jasky6','男','holter','亿比虚拟','566233205','456689955','Mysiry6','波动明显',0),
(null,'H1627167NDLVU','Jasky0','男','holter','亿比虚拟','566233245','456689955','Mysiry0','波动明显',0),
(null,'H1627168NDLVU','Jasky1','男','holter','亿比虚拟','566233255','456689955','Mysiry1','波动明显',1),
(null,'H1627169NDLVU','Jasky2','男','holter','亿比虚拟','5662326d5','456689955','Mysirya','波动明显',2),
