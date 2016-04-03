package cn.kchen.jsp.example;

import java.io.IOException;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import cn.kchen.jsp.src.MyException;
import cn.kchen.jsp.src.Wechat;

public class MyWechat extends Wechat {

	public MyWechat(HttpServletRequest request, HttpServletResponse response) throws IOException, MyException {
		super(request, response);
	}

    // 关注测试
	@Override
	public void respon_event_subscribe()
    {
        this.echoText("你来啦");
    }

    // 文本消息测试
	@Override
	public void respon_text()
	{
		this.echoText("你刚才说的是:" + mEventMessage.getContent());
	}

    // 点击自定义菜单测试
	@Override
	public void respon_event_click()
	{
        this.echoText("点我干嘛？");
	}
}
