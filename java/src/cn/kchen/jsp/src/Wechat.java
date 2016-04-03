package cn.kchen.jsp.src;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.PrintWriter;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

public class Wechat {

	public boolean mDebug = false;
	public String mToken = "weixin";
	
	public EventMessage mEventMessage;
	public String mKeyword;
	
    protected String mTemplateText        = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
    protected String mTemplateNewsBegin   = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>";
    protected String mTemplateNewsContent = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";
    protected String mTemplateNewsEnd     = "</Articles></xml>";
    protected String mTemplateCustomer    = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[transfer_customer_service]]></MsgType></xml>";
    protected String mTemplateImage       = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>";
	
	private BufferedReader mBufferedReader;
	private PrintWriter mPrintWriter;
	private MsgCryptor mMsgCryptor;
	
	public Wechat(HttpServletRequest request, HttpServletResponse response) throws IOException, MyException{

		// 设置编码
		request.setCharacterEncoding("UTF-8");
        response.setCharacterEncoding("UTF-8");

        // 获取参数
        mBufferedReader  = request.getReader();
        mPrintWriter     = response.getWriter();
        String timestamp = request.getParameter("timestamp");
        String nonce     = request.getParameter("nonce");
        String signature = request.getParameter("signature");
        String echostr   = request.getParameter("echostr");
        // 检查是否存在时间戳和随机数
        if (!mDebug && (timestamp == null || nonce == null)) {
        	throw new MyException("请通过微信访问");
        }
        
        // 创建加密模块对象
        try {
            mMsgCryptor = new MsgCryptor(mToken);
		} catch (Exception e) {
			e.printStackTrace();
        	throw new MyException("加密模块有误");
		}
        
        // 验证签名
        if(!mDebug){
        	boolean validResult = mMsgCryptor.mpVerifySig(signature, timestamp, nonce);
        	if (!validResult) {
            	throw new MyException("签名验证失败");
        	}
        }
        
        // 验证 TOKEN 则直接输出
        if (echostr != null) {
        	throw new MyException(echostr);
        }

		mEventMessage = XMLConverUtil.convertToObject(EventMessage.class, mBufferedReader);
	}
	
	private void echoMsg(String msg) {
        mPrintWriter.append(msg);
	}
	
	public void echoText(String text)
	{
		String resultStr = String.format(
				mTemplateText, 
				mEventMessage.getFromUserName(), 
				mEventMessage.getToUserName(), 
				System.currentTimeMillis()/1000,
				text);
		echoMsg(resultStr);
	}
	
	public void run()
	{	
		String MsgType = mEventMessage.getMsgType().toLowerCase();

		switch (MsgType) {
	        case "text":
	            mKeyword = mEventMessage.getContent();
	            respon_text();
	            break;
	        case "image":
	            mKeyword = mEventMessage.getPicUrl();
	            respon_image();
	            break;
	        case "voice":
	            mKeyword = mEventMessage.getRecognition();
	            respon_voice();
	            break;
	        case "video":
	            respon_video();
	            break;
	        case "shortvideo":
	            respon_shortvideo();
	            break;
	        case "link":
	            mKeyword = mEventMessage.getUrl();
	            respon_link();
	            break;
	        case "location":
	            mKeyword = mEventMessage.getLocation_X() + "." + mEventMessage.getLocation_Y();
	            respon_location();
	            break;
	        case "event":
	            respon_event();
	            break;
	        default:
	            respon_unknown();
	            break;
		}
	}

    // 文本消息
	public void respon_text() {}

    // 图片消息
	public void respon_image() {}

    // 语音消息
	public void respon_voice() {}

    // 视频消息
	public void respon_video() {}

    // 小视频消息
	public void respon_shortvideo() {}

    // 链接消息
	public void respon_link() {}

    // 位置消息
	public void respon_location() {}

    // 未知消息
	public void respon_unknown() {
        if (!mDebug) {
            return;
        }
        String template = "Servlet 报错啦！\r\n\r\n出现未知的消息类型%s";
        String content = String.format(template, mEventMessage.getEvent());
        echoText(content);
	}

    // 事件消息
	public void respon_event() {
		String event = mEventMessage.getEvent().toLowerCase();
		switch (event) {
	        case "subscribe":
	            respon_event_subscribe();
	            break;
	        case "unsubscribe":
	            respon_event_unsubscribe();
	            break;
	        case "click":
	            mKeyword = mEventMessage.getEventKey();
	            respon_event_click();
	            break;
	        case "view":
	            mKeyword = mEventMessage.getEventKey();
	            respon_event_view();
	            break;
	        case "location":
	            respon_event_location();
	            break;
	        case "scan":
	            respon_event_scan();
	            break;
	        case "scancode_push":
	            respon_event_scancode_push();
	            break;
	        case "scancode_waitmsg":
	            respon_event_scancode_waitmsg();
	            break;
	        case "pic_sysphoto":
	            respon_event_pic_sysphoto();
	            break;
	        case "pic_photo_or_album":
	            respon_event_pic_photo_or_album();
	            break;
	        case "pic_weixin":
	            respon_event_pic_weixin();
	            break;
	        case "location_select":
	            respon_event_location_select();
	            break;
	        case "enter_agent":
	            respon_event_enter_agent();
	            break;
	        case "batch_job_result":
	            respon_event_batch_job_result();
	        case "templatesendjobfinish":
	            respon_event_templatesendjobfinish();
	            break;
	        
	        default:
	            respon_event_unknown();
	            break;
		}
	}

    // 订阅事件（公众号、企业号）
    public void respon_event_subscribe() {}

    // 取消订阅（公众号、企业号）
    public void respon_event_unsubscribe() {}

    // 点击自定义菜单（公众号、企业号）
    public void respon_event_click() {}

    // 进入自定义菜单网址（公众号、企业号）
    public void respon_event_view() {}

    // 自动上报地理位置（公众号、企业号）
    public void respon_event_location() {}

    // 扫描带参数二维码事件（公众号）
    public void respon_event_scan() {}

    // 扫码推事件的事件推送（企业号、公众号）
    public void respon_event_scancode_push() {}

    // 扫码推事件且弹出“消息接收中”（企业号、公众号）
    public void respon_event_scancode_waitmsg() {}

    // 弹出系统拍照发图（企业号、公众号）
    public void respon_event_pic_sysphoto() {}

    // 弹出拍照或者相册发图（企业号、公众号）
    public void respon_event_pic_photo_or_album() {}

    // 弹出微信相册发图器（企业号、公众号）
    public void respon_event_pic_weixin() {}

    // 弹出地理位置选择器（企业号、公众号）
    public void respon_event_location_select() {}

    // 成员进入应用（企业号）
    public void respon_event_enter_agent() {}

    // 异步任务完成（企业号）
    public void respon_event_batch_job_result() {}

    // 模板消息的事件推送（公众号）
    public void respon_event_templatesendjobfinish() {}

    // 未知事件
    public void respon_event_unknown()
    {
        if (!mDebug) {
            return;
        }
        String template = "Servlet 报错啦！\r\n\r\n出现未知的事件类型%s";
        String content = String.format(template, mEventMessage.getEvent());
        echoText(content);
    }
}
