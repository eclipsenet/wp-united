function createFileTree(){$wpu("#phpbbpath").fileTree({root:"/",script:ajaxurl,multiFolder:false,loadMessage:fileTreeLdgText},function(e){var t=e.split("/");if(t.length>1){e=t.pop()}if(e=="config.php"){var n=t.join("/")+"/";$wpu("#phpbbpathshow").html(n).css("color","green");$wpu("#wpupathfield").val(n);$wpu("#phpbbpathgroup").hide("fade");$wpu("#txtchangepath").show();$wpu("#txtselpath").hide();$wpu("#wpucancelchange").hide();$wpu("#phpbbpathchooser").show("fade");$wpu("#wpusetup-submit").show();window.scrollTo(0,0)}});$wpu("#wpubackupentry").bind("keyup",function(){wpu_update_backuppath(true)});$wpu("#phpbbdocroot").bind("keyup",function(){wpu_update_backuppath(true)})}function resize_text_field(e){var t=$wpu("#wpu-measure");t.text(e.val());var n=t.width()+16;if(n<25)n=25;e.css("width",n+"px")}function wpu_update_backuppath(e){var t=$wpu("#phpbbdocroot");var n=$wpu("#wpubackupentry");var r=t.val()+n.val();resize_text_field(t);resize_text_field(n);r=r.replace(/\\/g,"/").replace(/\/\//g,"/");$wpu("#wpupathfield").val(r);var i=$wpu("#phpbbpathshow").html(r);if(e){i.css("color","orange")}}function wpu_filetree_trigger(e){if(e.length<50){wpuForceBackupEntry=true;wpuSwitchEntryType()}else{$wpu("#phpbbpath").show();$wpu("#wpubackupgroup").hide()}}function wpuSwitchEntryType(){if(!wpuUsingBackupEntry){wpuUsingBackupEntry=true;$wpu("#phpbbpath").hide();$wpu("#wpubackupgroup").show();$wpu("#wpuentrytype").text(autoText);wpu_update_backuppath(!wpuForceBackupEntry);$wpu("#wpusetup-submit").show()}else{if(!wpuForceBackupEntry){wpuUsingBackupEntry=false;$wpu("#phpbbpath").show();$wpu("#wpubackupgroup").hide();$wpu("#wpuentrytype").text(manualText);$wpu("#wpusetup-submit").hide()}}return false}function setupSettingsPage(){$wpu("#wputabs").tabs({select:function(e,t){window.location.hash=t.tab.hash}});$wpu("#phpbbpathchange").button();$wpu("#wputpladvancedstgs").button();$wpu(".wpuwhatis").button();var e=$wpu.QueryString["tab"];if(e!=undefined){$wpu("#wputabs").tabs("select","#"+e)}}function setPath(e){if(e=="setup"){$wpu("#phpbbpathgroup").hide();$wpu("#phpbbpathchooser").button();$wpu("#phpbbpathchooser").show();$wpu("#txtchangepath").show();$wpu("#txtselpath").hide()}$wpu("#phpbbpathshow").html(phpbbPath).css("color","green");$wpu("#wpupathfield").val(phpbbPath)}function setupHelpButtons(){$wpu(".wpuwhatis").click(function(){$wpu("#wpu-desc").text($wpu(this).attr("title"));$wpu("#wpu-dialog").dialog({modal:true,title:"WP-United Help",buttons:{Close:function(){$wpu(this).dialog("close")}}});return false})}function settingsFormSetup(){if($wpu("#wpuxpost").is(":checked")){$wpu("#wpusettingsxpostxtra").show();if($wpu("#wpuxpostcomments").is(":checked")){$wpu("#wpusettingsxpostcomments").show()}}if($wpu("#wpuloginint").is(":checked"))$wpu("#wpusettingsxpost").show();if($wpu("#wputplint").is(":checked")){$wpu("#wpusettingstpl").show();if($wpu("#wputplrev").is(":checked")){$wpu("#wputemplate-w-in-p-opts").hide()}else{$wpu("#wputemplate-p-in-w-opts").hide()}}$wpu("input[name=rad_tpl]").change(function(){$wpu("#wputemplate-p-in-w-opts").toggle();$wpu("#wputemplate-w-in-p-opts").toggle()});$wpu("#wpuloginint").change(function(){$wpu("#wpusettingsxpost").toggle("slide","slow")});$wpu("#wpuxpost").change(function(){$wpu("#wpusettingsxpostxtra").toggle("slide","slow")});$wpu("#wpuxpostcomments").change(function(){$wpu("#wpusettingsxpostcomments").toggle("slide","slow")});setCSSMLevel(cssmVal);$wpu("#wputplint").change(function(){$wpu("#wpusettingstpl").toggle("slide","slow");var e=$wpu(this).val()?2:0;setCSSMLevel(e);$wpu("#wpucssmlvl").slider("value",e)});$wpu("#wpucssmlvl").slider({value:cssmVal,min:0,max:2,step:1,change:function(e,t){setCSSMLevel(t.value)}})}function wpuChangePath(){$wpu("#phpbbpathgroup").show("fade");$wpu("#phpbbpathchooser").hide("fade");$wpu("#txtchangepath").hide();$wpu("#txtselpath").show();$wpu("#wpucancelchange").show();$wpu("#wpucancelchange").button();if(!wpuUsingBackupEntry){$wpu("#wpusetup-submit").hide()}else{$wpu("#wpusetup-submit").show()}return false}function wpuCancelChange(){$wpu("#phpbbpathgroup").hide("fade");$wpu("#phpbbpathchooser").show("fade");$wpu("#txtchangepath").show();$wpu("#txtselpath").hide();$wpu("#wpucancelchange").hide();$wpu("#wpusetup-submit").hide();return false}function setCSSMLevel(e){var t,n;if(e==0){t=statusCSSMDisabled;n=descCSSMDisabled}else if(e==1){t=statusCSSMMed;n=descCSSMMed}else if(e==2){t=statusCSSMFull;n=descCSSMFull}$wpu("#wpucssmlvlfield").val(e);$wpu("#cssmlvltitle").html(t);$wpu("#cssmlvldesc").html(n);try{$wpu("#cssmdesc").effect("highlight")}catch(r){}}function tplAdv(){$wpu("#wpusettingstpladv").toggle("fade");$wpu("#wutpladvshow").toggle();$wpu("#wutpladvhide").toggle();return false}function check_padding(e){var t=e||window.event;var n=t.keyCode||t.which;var r=String.fromCharCode(n);var i=/[0-9]/;if(!i.test(r)&&n!=8&&n!=46){t.returnValue=false;if(t.preventDefault)t.preventDefault()}}function default_padding(){$wpu("#wpupadtop").val("6");$wpu("#wpupadright").val("12");$wpu("#wpupadbtm").val("6");$wpu("#wpupadleft").val("12");return false}function wpu_transmit(e,t,n){$wpu("#wpustatus").hide();window.scrollTo(0,0);$wpu("#wputransmit").dialog({modal:true,title:"Connecting...",width:360,height:160,draggable:false,disabled:true,closeOnEscape:false,resizable:false,show:"puff"});$wpu(".ui-dialog-titlebar").hide();var r;if(e=="wp-united-setup"&&wpuUsingBackupEntry){wpu_update_backuppath(true)}wpu_setup_errhandler();r=$wpu("#"+t).serialize()+"&action=wpu_settings_transmit&type="+e+"&_ajax_nonce="+transmitNonce;$wpu.post(ajaxurl,r,function(t){t=$wpu.trim(t);var n;if(t.length>=2)n=t.substr(0,2);if(n=="OK"){window.location="admin.php?page="+e+"&msg=success"+"&tab="+window.location.hash.replace("#","");return}wpu_process_error(t)});return false}function wpu_setup_errhandler(){$wpu(document).ajaxError(function(e,t,n,r){if(!wpu_handling_error){wpu_handling_error=true;if(r==undefined){var r="Server "+t.status+" error. Please check your server logs for more information."}var i="<br />There was no page output.<br />";if(typeof t.responseText!=="undefined"){var s=t.responseText.split(/<body/i);if(s.length){i="<div "+s[1];s=i.split(/<\/body>/i)}i=s.length?s[0]+"</div>":i;i="<br />The page output was:<br /><div>"+i+"</div>"}wpu_process_error(errMsg="WP-United caught an error: "+n.url+" returned: "+r+i)}})}function wpu_process_error(e){if(e.indexOf("[ERROR]")==-1){var t="&wpudisable=1&action=wpu_disable&_ajax_nonce="+disableNonce;if(e==""){e=blankPageMsg}$wpu(document).ajaxError(function(){send_back_msg("admin.php?page=wp-united-setup&msg=fail",e)});$wpu.post(ajaxurl,t,function(t){send_back_msg("admin.php?page=wp-united-setup&msg=fail",e)})}else{e=e.replace(/\[ERROR\]/g,"");send_back_msg("admin.php?page=wp-united-setup&msg=fail",e)}}function send_back_msg(e,t){$wpu('<div id="escapetext"> </div>').appendTo("body");$wpu('<form action="'+e+'" method="post"><input type="hidden" name="msgerr" value="'+Base64.encode($wpu("#escapetext").text(t).html())+'"></input></form>').appendTo("body").submit()}function makeMsgSafe(e){e=Base64.encode(e);e=e.replace(/\+/ig,"%2B");e=e.replace(/\=/ig,"%3D");e=e.replace(/\//ig,"%2F");return escape(e)}function wpu_manual_disable(e){$wpu("#wputransmit").dialog({modal:true,title:connectingText,width:360,height:160,draggable:false,disabled:true,closeOnEscape:false,resizable:false,show:"puff"});$wpu(".ui-dialog-titlebar").hide();var t="wpudisableman=1&action=wpu_disableman&_ajax_nonce="+disableNonce;$wpu.post(ajaxurl,t,function(t){window.location="admin.php?page="+e});return false}function setupUserMapperPage(){$wpu(".wpuprocess").button({icons:{primary:"ui-icon-transferthick-e-w"}});$wpu(".wpuclear").button({icons:{primary:"ui-icon-cancel"}});setupAcpPopups();document.getElementById("wpumapscreen").onclick=function(e){var t=e.target||e.srcElement;var n=t.nodeName.toLowerCase();if(n=="a"){if(t.className.indexOf("wpuprofilelink")>-1){$wpu.colorbox({href:t.href,width:"88%",height:"92%",title:mapProfileTitle==undefined?"":mapProfileTitle,iframe:true})}return false}if(n!="span"||t.className.indexOf("ui-button")==-1){return false}t=t.parentNode;if(t.className.indexOf("ui-button-disabled")>-1){return false}if(t.id==undefined||t.id==""){if(t.className.indexOf("wpumapactionedit")>-1){$wpu.colorbox({href:t.href,width:"88%",height:"92%",title:mapEditTitle==undefined?"":mapEditTitle,iframe:true,onClosed:function(){wpuShowMapper(false)}});return false}return false}wpuProcessMapActionButton(t.id);return false};$wpu("#wpumapdisp select").bind("change",function(){if(!generatingMapper){wpuShowMapper(true)}});$wpu("#wpumapsearchbox").bind("keyup",function(){if(!generatingMapper){var e=$wpu(this).val();if(e!=mapTxtInputState){mapTxtInputState=e;wpuShowMapper(true)}}});wpuShowMapper(true)}function wpuSetupPermsMapper(){$wpu("#wputabs").tabs({select:function(e,t){window.location.hash=t.tab.hash},show:function(e,t){jsPlumb.repaintEverything()}});jsPlumb.importDefaults({DragOptions:{cursor:"pointer",zIndex:2e3},PaintStyle:{strokeStyle:"#666"},EndpointStyle:{width:20,height:16,strokeStyle:"#666"},Container:$wpu("#wpuplumbcanvas")});wpuEndPoint={endpoint:["Dot",{radius:15}],paintStyle:{fillStyle:"#000061"},scope:"wpuplumb",connectorStyle:{strokeStyle:"#000061",lineWidth:6},connector:["Bezier",{curviness:63}],maxConnections:10};wpuNeverEndPoint={endpoint:["Rectangle",{width:15,height:15}],paintStyle:{fillStyle:"#dd0000"},scope:"wpuplumbnever",connectorStyle:{strokeStyle:"#dd0000",lineWidth:6},connector:["Bezier",{curviness:63}],maxConnections:10};initPlumbing()}function wpuApplyPerms(){var e=jsPlumb.getConnections("wpuplumb");var t=jsPlumb.getConnections("wpuplumbnever");var n=[];for(var r=0;r<e.length;r++){n.push(e[r].sourceId.split(/-/g)[1]+"="+e[r].targetId.split(/-/g)[1])}var i=[];for(var r=0;r<t.length;r++){i.push(t[r].sourceId.split(/-/g)[1]+"="+t[r].targetId.split(/-/g)[1])}window.scrollTo(0,0);$wpu("#wpu-reload").dialog({modal:true,title:wpuConnectingText,width:360,height:160,draggable:false,disabled:true,closeOnEscape:false,resizable:false,show:"puff"});$wpu(".ui-dialog-titlebar").hide();$wpu("#wpu-desc").html("<strong>"+wpuProcessingText+"</strong><br />"+wpuWaitText);$wpu.post("admin.php?page=wpu-user-mapper","wpusetperms="+makeMsgSafe(n.join(","))+"&wpusetnevers="+makeMsgSafe(i.join(","))+"&_ajax_nonce="+firstMapActionNonce,function(e){e=$wpu.trim(e);var t;if(e.length>=2)t=e.substr(0,2);if(t=="OK"){}$wpu("#wpu-reload").dialog("destroy");window.location.reload()});return false;return false}function wpuClearPerms(){window.scrollTo(0,0);$wpu("#wpu-desc").html("<strong>"+wpuClearingText+"</strong><br />Please wait...");$wpu("#wpu-reload").dialog({modal:true,title:"Resetting...",width:360,height:160,draggable:false,disabled:true,closeOnEscape:false,resizable:false});$wpu(".ui-dialog-titlebar").hide();window.location.reload(1)}function wpuShowMapper(e){if(generatingMapper){return}generatingMapper=true;mapTxtInputState=$wpu("#wpumapsearchbox").val();if(e==true){$wpu("#wpufirstitem").val(0)}$wpu("#wpumapscreen").html('<div class="wpuloading"><p>'+wpuLoading+'</p><img src="'+imgLdg+'" /></div>');var t=$wpu("#wpumapdisp").serialize()+"&wpumapload=1&_ajax_nonce="+mapNonce;$wpu(document).ajaxError(function(e,t,n,r){if(r==undefined){var r="Server "+t.status+" error. Please check your server logs for more information."}$wpu("#wpumapscreen").html(errMsg=n.url+" returned: "+r)});$wpu.post("admin.php?page=wpu-user-mapper",t,function(e,t,n){if($wpu("#wpumapside").val()=="phpbb"){leftSide=phpbbText;rightSide=wpText}else{leftSide=wpText;rightSide=phpbbText}var r=$wpu(e).find("pagination").text();var i=$wpu(e).find("bulk").text();$wpu("#wpumappaginate1").html(r);$wpu("#wpumappaginate2").html(i+r);var s=Base64.decode($wpu(e).find("mapcontent").text());$wpu("#wpuoffscreen").html(s);var o=setTimeout("setupMapButtons()",200);var u=setTimeout("makeMapVisible()",1e3);wpuMapClearAll();wpuSuggCache={};wpuTypedMatches=new Array;$wpu("#wpumaptable input.wpuusrtyped").each(function(){$wpu(this).autocomplete({minLength:2,source:function(e,t){var n=$wpu("#wpumapside").val()=="phpbb"?"wp":"phpbb";if(e.term in wpuSuggCache){t(wpuSuggCache[e.term]);return}$wpu.ajax({url:"admin.php?page=wpu-user-mapper",dataType:"json",data:"term="+e.term+"&_ajax_nonce="+autofillNonce+"&pkg="+n,success:function(n){wpuSuggCache[e.term]=n;t(n)}})},select:function(e,t){var n=$wpu(this).attr("id").replace("wpumapsearch","wpumapfrom");var r=$wpu(this).attr("id").split(/-/ig)[1];var i=$wpu("#wpuuser"+r+" .wpuprofilelink").text();if(t.item.statuscode==1){$wpu(this).val(t.item.label);var s={username:i,touserid:t.item.value,tousername:t.item.label,toemail:t.item.desc};wpuTypedMatches[r]=s;$wpu("#wpuavatartyped"+r).html(t.item.avatar);$wpu("#"+n).bind("click",function(){return wpuMapIntegrateTyped(this)});$wpu("#"+n).button("enable")}else{$wpu("#"+n).unbind("click");$wpu("#"+n).button("disable");$wpu("#wpuavatartyped"+r).html("")}return false},focus:function(e,t){if(t.item.statuscode==1){$wpu(this).val(t.item.label)}return false}}).data("ui-autocomplete")._renderItem=function(e,t){var n=t.statuscode==0?"red":"green";return $wpu("<li></li>").data("ui-autocomplete-item",t).append("<a><small><strong>"+t.label+"</strong><br />"+t.desc+'<br /><em style="color: '+n+'">'+t.status+"</em></small></a>").appendTo(e)}});currAction=0;generatingMapper=false;if($wpu("#wpumapsearchbox").val()!=mapTxtInputState){clearTimeout(o);clearTimeout(u);wpuShowMapper(true)}})}function makeMapVisible(){$wpu("#wpumapscreen").html("");$wpu("#wpumapscreen").append($wpu("#wpumaptable"))}function wpuProcessMapActionButton(e){var t=e.split(/-/g);if(t.length<2){return false}var n,r;var i=t[1];var s=t[2];var o=s=="wp"?"phpbb":"wp";var u=t[3];var a=$wpu("#wpu"+s+"login"+u).text();switch(i){case"del":return wpuMapDel(u,s,a);break;case"delboth":n=t[4];r=$wpu("#wpu"+o+"login"+n).text();return wpuMapDelBoth(u,n,a,r);break;case"create":return wpuMapCreate(u,o,a);break;case"break":n=t[4];r=$wpu("#wpu"+o+"login"+n).text();return wpuMapBreak(u,n,a,r);break;case"sync":n=t[4];r=$wpu("#wpu"+o+"login"+n).text();return wpuMapSync(u,n,a,r);break}return false}function setupMapButtons(){$wpu("#wpumaptable a.wpumapactionsync").button({icons:{primary:"ui-icon-refresh"},text:false});$wpu("#wpumaptable a.wpumapactionbrk").button({icons:{primary:"ui-icon-scissors"},text:false});$wpu("#wpumaptable a.wpumapactioncreate").button({icons:{primary:"ui-icon-plusthick"},text:false});$wpu("#wpumaptable a.wpumapactiondel").button({icons:{primary:"ui-icon-trash"},text:false});$wpu("#wpumaptable  a.wpumapactionlnk").button({icons:{primary:"ui-icon-link"},text:false});$wpu("#wpumaptable a.wpumapactionlnktyped").button({icons:{primary:"ui-icon-link"},text:false,disabled:true});$wpu("#wpumaptable a.wpumapactionedit").button({icons:{primary:"ui-icon-gear"},text:false})}function wpuMapBulkActions(){var e=$wpu("#wpuquicksel").val();switch(e){case"del":$wpu("#wpumaptable .wpuintegnot a.wpumapactiondel").each(function(){if(!$wpu(this).button("widget").hasClass("ui-button-disabled")){wpuProcessMapActionButton($wpu(this).attr("id"))}});break;case"create":$wpu("#wpumaptable .wpuintegnot a.wpumapactioncreate").each(function(){if(!$wpu(this).button("widget").hasClass("ui-button-disabled")){wpuProcessMapActionButton($wpu(this).attr("id"))}});break;case"break":$wpu("#wpumaptable .wpuintegok a.wpumapactionbrk").each(function(){if(!$wpu(this).button("widget").hasClass("ui-button-disabled")){wpuProcessMapActionButton($wpu(this).attr("id"))}});break;case"sync":$wpu("#wpumaptable .wpuintegok a.wpumapactionsync").each(function(){if(!$wpu(this).button("widget").hasClass("ui-button-disabled")){wpuProcessMapActionButton($wpu(this).attr("id"))}});break}return false}function setupAcpPopups(){$wpu("#wpumapscreen a.wpuacppopup, #wpumaptab-perms a.wpuacppopup").colorbox({width:"88%",height:"92%",title:acpPopupTitle==undefined?"":acpPopupTitle,iframe:true,onClosed:function(){window.scrollTo(0,0);$wpu("#wpu-desc").html("<strong>"+wpuReloading+"</strong><br />Please wait...");$wpu("#wpu-reload").dialog({modal:true,title:wpuReloading,width:360,height:160,draggable:false,disabled:true,closeOnEscape:false,resizable:false});$wpu(".ui-dialog-titlebar").hide();window.location.reload(1)}})}function showPanel(){if(!panelOpen){$wpu("#wpumapcontainer").splitter({type:"v",sizeRight:225});$wpu("#wpumapscreen").css("overflow-y","auto");$wpu("#wpumappanel").show("slide",{direction:"right"});$wpu("#wpumappanel h3").prepend('<span class="ui-icon ui-icon-triangle-1-e"></span>');$wpu("#wpumappanel h3 .ui-icon").click(function(){togglePanel($wpu(this))});panelOpen=true}panelHidden=true;togglePanel($wpu("#wpumappanel h3 .ui-icon"))}function closePanel(){if(panelOpen){$wpu("#wpumapcontainer").trigger("resize",[$wpu("#wpumapcontainer").width()]);$wpu("#wpumapcontainer .vsplitbar").css("display","none");panelHidden=true}}function togglePanel(e){if(!panelHidden){e.removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-w");$wpu("#wpumapcontainer").trigger("resize",[$wpu("#wpumapcontainer").width()-20]);panelHidden=true}else{e.removeClass("ui-icon-triangle-1-w").addClass("ui-icon-triangle-1-e");$wpu("#wpumapcontainer .vsplitbar").css("display","block");$wpu("#wpumapcontainer").trigger("resize",[$wpu("#wpumapcontainer").width()-225]);panelHidden=false}}function wpuMapIntegrateTyped(e){if($wpu(e).button("widget").hasClass("ui-state-disabled")){return false}var t=$wpu(e).attr("id").split(/-/ig)[1];if(t in wpuTypedMatches){return wpuMapIntegrate(e,t,wpuTypedMatches[t].touserid,wpuTypedMatches[t].username,wpuTypedMatches[t].tousername,"",wpuTypedMatches[t].toemail)}return false}function wpuMapIntegrate(e,t,n,r,i,s,o){if($wpu(e).button("widget").hasClass("ui-state-disabled")){return false}showPanel();var u=actionIntegrate;var a=actionIntegrateDets.replace("%1$s",leftSide).replace("%2$s","<em>"+r+"</em>").replace("%3$s",rightSide).replace("%4$s","<em>"+i+"</em>");var f=wpuMapActions.length;var l='<li id="wpumapaction'+f+'"><strong>'+u+"</strong> "+a+"</li>";var c=$wpu("#wpumapside").val();if(c=="wp"&&(t==currWpUser||n==currPhpbbUser)||c=="phpbb"&&(t==currPhpbbUser||n==currWpUser)){selContainsCurrUser=true}wpuMapActions.push({type:"integrate",userid:t,intuserid:n,desc:u+" "+a,"package":c});$wpu("#wpupanelactionlist").append(l);$wpu("#wpuuser"+t).find("a.ui-button:not(.wpumapactionedit)").button("disable");if($wpu(e).attr("id").indexOf("wpumapfrom")>-1){$wpu("#"+$wpu(e).attr("id").replace("wpumapfrom","wpumapsearch")).attr("disabled","disabled");$wpu(e).unbind("click")}return false}function wpuMapSync(e,t,n,r){showPanel();var i=actionSync;var s=actionSyncDets.replace("%1$s","<em>"+n+"</em>").replace("%2$s","<em>"+r+"</em>");var o=wpuMapActions.length;var u='<li id="wpumapaction'+o+'"><strong>'+i+"</strong> "+s+"</li>";var a=$wpu("#wpumapside").val();if(a=="wp"&&(e==currWpUser||t==currPhpbbUser)||a=="phpbb"&&(e==currPhpbbUser||t==currWpUser)){selContainsCurrUser=true}wpuMapActions.push({type:"sync",userid:e,intuserid:t,desc:i+" "+s,"package":a});$wpu("#wpupanelactionlist").append(u);$wpu("#wpuuser"+e).find("a.ui-button:not(.wpumapactionedit)").button("disable");return false}function wpuMapBreak(e,t,n,r){showPanel();var i=actionBreak;var s=actionBreakDets.replace("%1$s","<em>"+n+"</em>").replace("%2$s","<em>"+r+"</em>");var o=wpuMapActions.length;var u='<li id="wpumapaction'+o+'"><strong>'+i+"</strong> "+s+"</li>";var a=$wpu("#wpumapside").val();if(a=="wp"&&(e==currWpUser||t==currPhpbbUser)||a=="phpbb"&&(e==currPhpbbUser||t==currWpUser)){selContainsCurrUser=true}wpuMapActions.push({type:"break",userid:e,intuserid:t,desc:i+" "+s,"package":a});$wpu("#wpupanelactionlist").append(u);$wpu("#wpuuser"+e).find("a.ui-button:not(.wpumapactionedit)").button("disable");return false}function wpuMapDelBoth(e,t,n,r){showPanel();var i=actionDelBoth;var s=actionDelBothDets.replace("%1$s","<em>"+n+"</em>").replace("%2$s",leftSide).replace("%3$s","<em>"+r+"</em>").replace("%4$s",rightSide);var o=wpuMapActions.length;var u='<li id="wpumapaction'+o+'"><strong>'+i+"</strong> "+s+"</li>";var a=$wpu("#wpumapside").val();if(a=="wp"&&(e==currWpUser||t==currPhpbbUser)||a=="phpbb"&&(e==currPhpbbUser||t==currWpUser)){selContainsCurrUser=true}wpuMapActions.push({type:"delboth",userid:e,intuserid:t,desc:i+" "+s,"package":a});$wpu("#wpupanelactionlist").append(u);$wpu("#wpuuser"+e).find("a.ui-button:not(.wpumapactionedit)").button("disable");return false}function wpuMapDel(e,t,n){var r=t=="phpbb"?phpbbText:wpText;showPanel();var i=actionDel;var s=actionDelDets.replace("%1$s","<em>"+n+"</em>").replace("%2$s",r);var o=wpuMapActions.length;var u='<li id="wpumapaction'+o+'"><strong>'+i+"</strong> "+s+"</li>";if(t=="wp"&&e==currWpUser||t=="phpbb"&&e==currPhpbbUser){selContainsCurrUser=true}wpuMapActions.push({type:"del",userid:e,desc:i+" "+s,"package":t});$wpu("#wpupanelactionlist").append(u);var a=t=="phpbb"?"wp":"phpbb";$wpu("#wpuuser"+e).find("a.ui-button:not(.wpumapactionedit)").button("disable");$wpu("#wpuuser"+e).find("div.wpu"+a+"user a.wpumapactiondel").button("enable");$wpu("#wpuavatartyped"+e).html("");$wpu("#wpumapsearch-"+e).attr("disabled","disabled");return false}function wpuMapCreate(e,t,n){var r=t=="phpbb"?phpbbText:wpText;showPanel();var i=actionCreate;var s=actionCreateDets.replace("%1$s","<em>"+n+"</em>").replace("%2$s",r);var o=wpuMapActions.length;var u='<li id="wpumapaction'+o+'"><strong>'+i+"</strong> "+s+"</li>";if(t=="wp"&&e==currPhpbbUser||t=="phpbb"&&e==currWpUser){selContainsCurrUser=true}wpuMapActions.push({type:"createin",userid:e,desc:i+" "+s,"package":t});$wpu("#wpupanelactionlist").append(u);$wpu("#wpuuser"+e).find("a.ui-button:not(.wpumapactionedit)").button("disable");$wpu("#wpuavatartyped"+e).html("");$wpu("#wpumapsearch-"+e).attr("disabled","disabled");return false}function wpuMapClearAll(){wpuMapActions=new Array;$wpu("#wpupanelactionlist").html("");closePanel();$wpu("#wpumapscreen").find("a.wpumapactionbrk, "+"a.wpumapactiondel, "+"a.wpumapactionlnk, "+"a.wpumapactioncreate, "+"a.wpumapactionsync").button("enable");$wpu("#wpumapscreen a.wpumapactionlnktyped").button("disable");$wpu("#wpumapscreen a.wpuusrtyped").val("");$wpu("#wpumapscreen input.wpuusrtyped").removeAttr("disabled");$wpu("#wpumapscreen div.wpuavatartyped").html("");return false}function wpuMapPaginate(e){var t=e.href.indexOf("start=")>-1?e.href.split("start=")[1]:0;$wpu("#wpufirstitem").val(t);wpuShowMapper(false);return false}function wpuProcess(){window.scrollTo(0,0);$wpu("#wpu-reload").dialog({modal:true,title:"Applying actions...",width:360,height:220,draggable:false,disabled:true,closeOnEscape:false,resizable:false,show:"puff",buttons:{"Cancel remaining actions":function(){wpuProcessFinished()}}});$wpu("#wpuldgimg").show();numActions=wpuMapActions.length;wpuNextAction(firstMapActionNonce);return false}function wpuNextAction(e){el=$wpu("#wpupanelactionlist li:first");if(el.length){wpuProcessNext(el,e)}else{wpuProcessFinished()}}function wpuProcessNext(e,t){var n,r,i;var s="";var o=0;currAction++;n=parseInt(e.attr("id").replace("wpumapaction",""));$wpu(e).remove();s=wpuMapActions[n]["desc"];$wpu("#wpu-desc").html("<strong>Processing action "+(currAction+1)+" of "+(numActions+1)+"</strong><br />"+s);$wpu(document).ajaxError(function(e,t,n,r){if(r==undefined){var r="Server "+t.status+" error. Please check your server logs for more information."}$wpu("#wpu-desc").html(errMsg="An error occurred. The remaining actions have not been processed. Error: "+r)});r=new Array;for(actionKey in wpuMapActions[n]){if(actionKey!="desc"){r.push(actionKey+"="+wpuMapActions[n][actionKey])}}i=r.join("&");i+="&wpumapaction=1&_ajax_nonce="+t;$wpu.post("admin.php?page=wpu-user-mapper",i,function(e){var t=$wpu(e).find("status").text();var n=$wpu(e).find("details").text();var r=$wpu(e).find("nonce").text();t=$wpu.trim(t);var i;if(t.length>=2)i=t.substr(0,2);if(i=="OK"){wpuNextAction(r)}else{$wpu("#wpu-reload").dialog("destroy");$wpu("#wpu-desc").html(errMsg="An error occurred on the server. The remaining actions have not been processed. Error: "+n);$wpu("#wpu-reload").dialog({modal:true,title:"Error",width:360,height:220,draggable:false,resizable:false,show:"puff",buttons:{OK:function(){wpuProcessFinished()}}});$wpu("#wpuldgimg").hide()}});return false}function wpuProcessFinished(){$wpu("#wpu-reload").dialog("destroy");if(selContainsCurrUser){window.location.reload()}else{wpuShowMapper(false)}}function wpu_hardened_init(){if(!wpuHasInited){wpuHasInited=true;wpu_hardened_init_tail()}}var $wpu=jQuery.noConflict();(function(e){e.QueryString=function(e){if(e=="")return{};var t={};for(var n=0;n<e.length;++n){var r=e[n].split("=");if(r.length!=2)continue;t[r[0]]=decodeURIComponent(r[1].replace(/\+/g," "))}return t}(window.location.search.substr(1).split("&"))})(jQuery);var wpuUsingBackupEntry=false;var wpuForceBackupEntry=false;var wpu_handling_error=false;var leftSide,rightSide;var wpuMapActions=new Array;var wpuTypedMatches=new Array;var wpuSuggCache;var panelOpen=false;var panelHidden=false;var wpuEndPoint;var wpuNeverEndPoint;var selContainsCurrUser=false;var generatingMapper=false;var mapTxtInputState="";var numActions;var currAction=0;var wpuHasInited=false;var Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/\r\n/g,"\n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}
