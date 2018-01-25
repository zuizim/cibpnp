function createLink(c,m,v)
{
	if(v)  { v=v.split("&"); for(i=0; i<v.length; i++) { v[i]=v[i].split("="); } }
	link="/"+c+"/"+m;
	if(v) { link+="?"+v[0][0]+"="+v[0][1]; for(i=1; i<v.length; i++) { link+="&"+v[i][0]+"="+v[i][1]; } }
	return link;
}

