jQuery(function() {
	var imgCount = 0;
    var $ = jQuery,    // just in case. Make sure it's not an other libaray.

        $wrap = $('#uploader'),

        // 图片容器
        $queue = $('<ul class="filelist"></ul>')
            .appendTo( $wrap.find('.queueList') ),

        // 状态栏，包括进度和控制按钮
        $statusBar = $wrap.find('.statusBar'),

        // 文件总体选择信息。
        $info = $statusBar.find('.info'),

        // 没选择文件之前的内容。
        $placeHolder = $wrap.find('.placeholder'),

        // 总体进度条
        $progress = $statusBar.find('.progress').hide(),

        // 添加的文件数量
        fileCount = 0,

        // 添加的文件总大小
        fileSize = 0,

        // 优化retina, 在retina下这个值是2
        ratio = window.devicePixelRatio || 1,

        // 缩略图大小
        thumbnailWidth = 110 * ratio,
        thumbnailHeight = 110 * ratio,

        // 可能有pedding, ready, uploading, confirm, done.
        state = 'pedding',

        // 所有文件的进度信息，key为file id
        percentages = {},

        supportTransition = (function(){
            var s = document.createElement('p').style,
                r = 'transition' in s ||
                      'WebkitTransition' in s ||
                      'MozTransition' in s ||
                      'msTransition' in s ||
                      'OTransition' in s;
            s = null;
            return r;
        })(),

        // WebUploader实例
        uploader;

    if ( !WebUploader.Uploader.support() ) {
        alert( 'Web Uploader 不支持您的浏览器！如果你使用的是IE浏览器，请尝试升级 flash 播放器');
        throw new Error( 'WebUploader does not support the browser you are using.' );
    }

    // 实例化
    uploader = WebUploader.create({
        pick: {
            id: '#filePicker',
            label: '点击选择图片'
        },
        dnd: '#uploader .queueList',
        paste: document.body,

        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/jpg,image/jpeg,image/png'
        },

        // swf文件路径
        swf: window.BASE_URL + '/Uploader.swf',

        disableGlobalDnd: true,
        auto:true,

        chunked: true,
        sendAsBinary:true,
        // server: 'http://webuploader.duapp.com/server/fileupload.php',
        server: path,
        formData: {"imageCate" : "goods"},
        fileNumLimit: 3,
        fileSizeLimit: 3 * 1024 * 1024,
        fileSingleSizeLimit: 1 * 1024 * 1024
    });

    // 添加“添加文件”的按钮，
    uploader.addButton({
        id: '#filePicker2',
        label: '继续添加'
    });

    // 当有文件添加进来时执行，负责view的创建
    function addFile( file ) {
        var $li = $( '<li id="' + file.id + '">' +
                '<p class="title">' + file.name + '</p>' +
                '<p class="imgWrap"></p>'+
                '<p class="progress"><span></span></p>' +
                '</li>' ),

            $btns = $('<div class="file-panel">' +
                '<span class="cancel">删除</span>' +
                '<span class="rotateRight">向右旋转</span>' +
                '<span class="rotateLeft">向左旋转</span></div>').appendTo( $li ),
            $prgress = $li.find('p.progress span'),
            $wrap = $li.find( 'p.imgWrap' ),
            $info = $('<p class="error"></p>'),

            showError = function( code ) {
                switch( code ) {
                    case 'exceed_size':
                        text = '文件大小超出';
                        break;

                    case 'interrupt':
                        text = '上传暂停';
                        break;

                    default:
                        text = '上传失败，请重试';
                        break;
                }

                $info.text( text ).appendTo( $li );
            };

        if ( file.getStatus() === 'invalid' ) {
            showError( file.statusText );
        } else {
            // @todo lazyload
            $wrap.text( '预览中' );
            uploader.makeThumb( file, function( error, src ) {
                if ( error ) {
                    $wrap.text( '不能预览' );
                    return;
                }

                var img = $('<img src="'+src+'">');
                $wrap.empty().append( img );
            }, thumbnailWidth, thumbnailHeight );

            percentages[ file.id ] = [ file.size, 0 ];
            file.rotation = 0;
        }

        file.on('statuschange', function( cur, prev ) {
            if ( prev === 'progress' ) {
                $prgress.hide().width(0);
            } else if ( prev === 'queued' ) {
                //$li.off( 'mouseenter mouseleave' );
                //$btns.remove();
            }

            // 成功
            if ( cur === 'error' || cur === 'invalid' ) {
                console.log( file.statusText );
                showError( file.statusText );
                percentages[ file.id ][ 1 ] = 1;
            } else if ( cur === 'interrupt' ) {
                showError( 'interrupt' );
            } else if ( cur === 'queued' ) {
                percentages[ file.id ][ 1 ] = 0;
            } else if ( cur === 'progress' ) {
                $info.remove();
                $prgress.css('display', 'block');
            } else if ( cur === 'complete' ) {
                $li.append( '<span class="success"></span>' );
            }

            $li.removeClass( 'state-' + prev ).addClass( 'state-' + cur );
        });

        $li.on( 'mouseenter', function() {
            $btns.stop().animate({height: 30});
        });

        $li.on( 'mouseleave', function() {
            $btns.stop().animate({height: 0});
        });

        $btns.on( 'click', 'span', function() {
            var index = $(this).index(),
                deg;

            switch ( index ) {
                case 0:
                    uploader.removeFile( file );
                    if(fileCount < 6){
                    	$("#filePicker2").show();
                    }
                    return;

                case 1:
                    moveRight($li);
                    break;

                case 2:
                    moveLeft($li);
                    break;
            }

            if ( supportTransition ) {
                deg = 'rotate(' + file.rotation + 'deg)';
                $wrap.css({
                    '-webkit-transform': deg,
                    '-mos-transform': deg,
                    '-o-transform': deg,
                    'transform': deg
                });
            } else {
                $wrap.css( 'filter', 'progid:DXImageTransform.Microsoft.BasicImage(rotation='+ (~~((file.rotation/90)%4 + 4)%4) +')');
            }


        });

        $li.appendTo( $queue );
    }

    function editFile(file){
    	fileCount++;
    	var $li = $( '<li id="goods_' + file.id + '">' +
                '<p class="title">' + file.name + '</p>' +
                '<p class="imgWrap"></p>'+
                '<p class="progress"><span></span></p>' +
                '</li>' ),

            $btns = $('<div class="file-panel">' +
                '<span class="cancel">删除</span>' +
                '<span class="rotateRight">向右旋转</span>' +
                '<span class="rotateLeft">向左旋转</span></div>').appendTo( $li ),
            $prgress = $li.find('p.progress span'),
            $wrap = $li.find( 'p.imgWrap' ),
            $info = $('<p class="error"></p>');

    	var src = path+'/imgupload.action?method=showImage&fileName='+file.filename;
        img = $('<img src="'+src+'" style="height:100%;">');
        $wrap.empty().append( img );

        $li.on( 'mouseenter', function() {
            $btns.stop().animate({height: 30});
        });

        $li.on( 'mouseleave', function() {
            $btns.stop().animate({height: 0});
        });

        $btns.on( 'click', 'span', function() {
            var index = $(this).index(),
                deg;

            switch ( index ) {
                case 0:
                    removeGoodsFile( file );
                    return;

                case 1:
                	moveRight($li);
                    break;

                case 2:
                	moveLeft($li);
                    break;
            }

            if ( supportTransition ) {
                deg = 'rotate(' + file.rotation + 'deg)';
                $wrap.css({
                    '-webkit-transform': deg,
                    '-mos-transform': deg,
                    '-o-transform': deg,
                    'transform': deg
                });
            } else {
                $wrap.css( 'filter', 'progid:DXImageTransform.Microsoft.BasicImage(rotation='+ (~~((file.rotation/90)%4 + 4)%4) +')');
            }
        });

        $placeHolder.addClass( 'element-invisible' );
        $statusBar.show();
        $statusBar.removeClass('element-invisible');
        $li.appendTo( $queue );
        updateStatus();
    }

    /**
     * 图片像向左移动
     */
    function moveLeft($li){
    	var thisId = $li.attr("id");
    	var prevId = $li.prev().attr("id");
    	var thisIndex = thisId.substr(thisId.length - 1,1);

    	if(thisIndex == '0'){
    		return;
    	}
    	var temp = thisId.substr(0,thisId.length-1);
    	$li.attr("id",temp+prevId.substr(prevId.length - 1,1));
    	$li.prev().attr("id",temp+thisId.substr(thisId.length - 1,1));

    	var thisPathObj = $("input[name='goodsImage_"+(parseInt(thisIndex)+1)+"']");
    	var thisPath = thisPathObj.attr("name");
    	var prevPath = thisPathObj.prev().attr("name");

    	thisPathObj.attr("name","goodsImage_"+prevPath.substr(prevPath.length - 1,1));
    	thisPathObj.prev().attr("name","goodsImage_"+thisPath.substr(thisPath.length - 1,1));

    	thisPathObj.insertBefore(thisPathObj.prev());
    	$li.insertBefore($li.prev());
    }

    /**
     * 图片向右移动
     */
    function moveRight($li){
    	var thisId = $li.attr("id");
    	var nextId = $li.next().attr("id");
    	var photoCount = $li.parent().children().length;
    	var thisIndex = thisId.substr(thisId.length - 1,1);

    	if(thisIndex == (photoCount-1)){
    		return;
    	}
    	var temp = thisId.substr(0,thisId.length-1);
    	$li.attr("id",temp+nextId.substr(nextId.length - 1,1));
    	$li.next().attr("id",temp+thisId.substr(thisId.length - 1,1));

    	var thisPathObj = $("input[name='goodsImage_"+(parseInt(thisIndex)+1)+"']");
    	var thisPath = thisPathObj.attr("name");
    	var prevPath = thisPathObj.next().attr("name");

    	thisPathObj.attr("name","goodsImage_"+prevPath.substr(prevPath.length - 1,1));
    	thisPathObj.next().attr("name","goodsImage_"+thisPath.substr(thisPath.length - 1,1));

    	thisPathObj.insertAfter(thisPathObj.next());

    	$li.insertAfter($li.next());
    }

    //修改商品-->删除商品图片
    function removeGoodsFile(file){
    	$("#goods_"+file.id).remove();
    	$("#goods_path_"+file.pathid).remove();
    	fileCount--;
    	if ( !fileCount ) {
    		$placeHolder.removeClass( 'element-invisible' );
            $queue.parent().removeClass('filled');
            $queue.hide();
            $statusBar.addClass( 'element-invisible' );
            uploader.refresh();
            setState( 'pedding' );
        }else{
        	$("#filePicker2").show();
        }
    	updateStatus();
    }

    // 负责view的销毁
    function removeFile( file ) {
    	$("#file_"+file.id).remove();
        var $li = $('#'+file.id);

        delete percentages[ file.id ];
        updateTotalProgress();
        $li.off().find('.file-panel').off().end().remove();
    }

    function updateTotalProgress() {
        var loaded = 0,
            total = 0,
            spans = $progress.children(),
            percent;

        $.each( percentages, function( k, v ) {
            total += v[ 0 ];
            loaded += v[ 0 ] * v[ 1 ];
        } );

        percent = total ? loaded / total : 0;

        spans.eq( 0 ).text( Math.round( percent * 100 ) + '%' );
        spans.eq( 1 ).css( 'width', Math.round( percent * 100 ) + '%' );
        updateStatus();
    }

    function updateStatus() {
        var text = '', stats;

        if(fileCount == 6){
        	$("#filePicker2").hide();
        }

        if ( state === 'ready' ) {
            text = '选中' + fileCount + '张图片，共' +
                    WebUploader.formatSize( fileSize ) + '。';
        } else if ( state === 'confirm' ) {
            stats = uploader.getStats();
            if ( stats.uploadFailNum ) {
                text = '已成功上传' + stats.successNum+ '张照片至XX相册，'+
                    stats.uploadFailNum + '张照片上传失败，<a class="retry" href="#">重新上传</a>失败图片'
            }

        } else {
            stats = uploader.getStats();
            text = '共' + fileCount + '张'+
                    '，已上传' + stats.successNum + '张';

            if ( stats.uploadFailNum ) {
                text += '，失败' + stats.uploadFailNum + '张';
            }
        }

        $info.html( text );
    }

    function setState( val ) {
        var file, stats;

        if ( val === state ) {
            return;
        }

        state = val;

        switch ( state ) {
            case 'pedding':
                $placeHolder.removeClass( 'element-invisible' );
                $queue.parent().removeClass('filled');
                $queue.hide();
                $statusBar.addClass( 'element-invisible' );
                uploader.refresh();
                break;

            case 'ready':
                $placeHolder.addClass( 'element-invisible' );
                $( '#filePicker2' ).removeClass( 'element-invisible');
                $queue.parent().addClass('filled');
                $queue.show();
                $statusBar.removeClass('element-invisible');
                uploader.refresh();
                break;

            case 'uploading':
                $( '#filePicker2' ).addClass( 'element-invisible' );
                $progress.show();
                break;

            case 'paused':
                $progress.show();
                break;

            case 'confirm':
                $progress.hide();
                $( '#filePicker2' ).removeClass( 'element-invisible');

                stats = uploader.getStats();
                if ( stats.successNum && !stats.uploadFailNum ) {
                    setState( 'finish' );
                    return;
                }
                break;
            case 'finish':
                stats = uploader.getStats();
                if ( stats.successNum ) {
                } else {
                    // 没有成功的图片，重设
                    state = 'done';
                    location.reload();
                }
                break;
        }

        updateStatus();
    }

    uploader.onUploadProgress = function( file, percentage ) {
        var $li = $('#'+file.id),
            $percent = $li.find('.progress span');

        $percent.css( 'width', percentage * 100 + '%' );
        percentages[ file.id ][ 1 ] = percentage;
        updateTotalProgress();
    };

    uploader.on( 'uploadSuccess', function( file,data ) {
        console.log(data);
    	imgCount++;
    	var html = "<input type='hidden' value='"+data.filePath+"' name='goodsImage_"+imgCount+"'  id='file_"+file.id+"'>";
    	$("#goods_image_count").val(imgCount);
    	$("#goodsForm").append(html);
    });

    uploader.on( 'ready',function(){
    	if(false){
    		$("#filePicker2").find(" > div:last").attr("style","position: absolute; top: 0px; left: 10px; width: 94px; height: 42px; overflow: hidden; bottom: auto; right: auto;");
    		$(".queueList").addClass("filled");
    		ajax.remoteCall(path+"/com.nzit.mps.dao.GoodsDao:getGoodsPhoto",[goodsId],function(reply){
        		var data = reply.getResult();
        		if(data.success == true){
        			var goodsPhotosList = data.goodsPhotosList;
        			for(var i=0;i<goodsPhotosList.length;i++){
        				imgCount++;
        		    	var html = "<input type='hidden' value='"+goodsPhotosList[i].normal_image+"' name='goodsImage_"+imgCount+"'  id='goods_path_"+(goodsPhotosList[i].order_no-1)+"'>";
        		    	$("#goods_image_count").val(imgCount);
        		    	$("#goodsForm").append(html);

        				var file = new Array();
        				file.id = "WU_FILE_"+(goodsPhotosList[i].order_no-1);
        				file.filename = goodsPhotosList[i].normal_image;
        				file.pathid = (goodsPhotosList[i].order_no-1);
        				file.index = (goodsPhotosList[i].order_no);
        				editFile(file);
        			}
        		}
        	});
    	}
    });

    uploader.onFileQueued = function( file ) {
        fileCount++;
        fileSize += file.size;

        if ( fileCount === 1 ) {
            $placeHolder.addClass( 'element-invisible' );
            $statusBar.show();
        }

        addFile( file );
        setState( 'ready' );
        updateTotalProgress();
    };

    uploader.onFileDequeued = function( file ) {
        fileCount--;
        fileSize -= file.size;

        if ( !fileCount ) {
            setState( 'pedding' );
        }

        removeFile( file );
        updateTotalProgress();

    };

    uploader.on( 'all', function( type ) {
        var stats;
        switch( type ) {
            case 'uploadFinished':
                setState( 'confirm' );
                break;

            case 'startUpload':
                setState( 'uploading' );
                break;

            case 'stopUpload':
                setState( 'paused' );
                break;

        }
    });

    uploader.onError = function( code ) {
    	switch(code){
    		case 'F_DUPLICATE':
    			alert("该文件已经被选择了！");
    			break;
    		case 'Q_EXCEED_NUM_LIMIT':
    			alert("最多上传6张图片！");
    			break;
    		case 'F_EXCEED_SIZE':
    			alert("单张图片最大不能超过1M!");
    			break;
    		case 'Q_EXCEED_SIZE_LIMIT':
    			alert("所有文件最大不能超过6M！");
    			break;
    		case '文件类型不正确或者是空文件':
    			alert("文件类型不正确或者是空文件");
    			break;
    		default:
    			alert("文件类型不正确或者是空文件!");
    			break;
    	}
    };

    $info.on( 'click', '.retry', function() {
        uploader.retry();
    } );

    $info.on( 'click', '.ignore', function() {
        alert( 'todo' );
    } );

    updateTotalProgress();
});
