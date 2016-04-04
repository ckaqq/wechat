package cn.kchen.jsp.src;

import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.Arrays;

import com.qq.weixin.mp.aes.*;

public class MsgCryptor extends WXBizMsgCrypt {

	public String token, encodingAesKey, appId;
	
	public MsgCryptor(String token, String encodingAesKey, String appId) throws AesException {
		super(token, encodingAesKey, appId);
		this.token = token;
		this.encodingAesKey = encodingAesKey;
		this.appId = appId;
	}
	
	public MsgCryptor(String token) throws AesException {
		super(token, "0000000000000000000000000000000000000000000", "");
		this.token = token;
		this.encodingAesKey = "0000000000000000000000000000000000000000000";
		this.appId = "";
	}
	
	public boolean mpVerifySig(String signature, String timestamp, String nonce)
	{
		String[] array = new String[] { this.token, timestamp, nonce };
		return variySha1Sign(signature, array);
	}

	public boolean qyVerifySig(String signature, String timestamp, String nonce, String echostr) {
		String[] array = new String[] { this.token, timestamp, nonce, echostr };
		return variySha1Sign(signature, array);
	}

	public String qyEchoStr(String signature, String timestamp, String nonce, String echostr) {
        if (this.encodingAesKey.length() != 43){
            return "ERR: 40004\n\n";
        }
		try {
			return verifyUrl(signature, timestamp, nonce, echostr);
		} catch (Exception e) {
			return "";
		}
	}
	
	public String sha1(String str)
	{
		try {
			MessageDigest md = MessageDigest.getInstance("SHA-1");
			md.update(str.getBytes());
			byte[] digest = md.digest();

			StringBuffer hexstr = new StringBuffer();
			String shaHex = "";
			for (int i = 0; i < digest.length; i++) {
				shaHex = Integer.toHexString(digest[i] & 0xFF);
				if (shaHex.length() < 2) {
					hexstr.append(0);
				}
				hexstr.append(shaHex);
			}
			return hexstr.toString();
		} catch (NoSuchAlgorithmException e) {
			return "";
		}
	}
	
	private boolean variySha1Sign(String signature,String[] array)
	{
		StringBuffer sb = new StringBuffer();
		Arrays.sort(array);
		for (int i = 0; i < array.length; i++) {
			sb.append(array[i]);
		}
		return signature.equals(sha1(sb.toString()));
	}
}
