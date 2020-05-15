<?php

namespace Sxstem\Mails;
use Exception;
class Smtp extends Config
{

	/**
	 * Connect smtp
	 * @username string
	 * @password string
	 */
	public function __construct($username, $password)
	{
		$this->type = 'smtp';

		//设置用户名
		$this->username = $username;

		//设置邮箱类型url：163，QQ等
		$host = explode('@', $username)[1];
		$this->setConfig($host);

		//设置密码
		$this->password = $password;

		$this->smtpOpen();
	}

	private function ini()
	{
		// 握手
		$this->execute("EHLO " . $_SERVER['SERVER_NAME']);
		// 登录验证
		if ($this->smtp_proxy == '587')
		{
			$this->execute("STARTTLS");
			$this->execute("EHLO " . $_SERVER['SERVER_NAME']);
		}
		$this->execute("AUTH LOGIN");
		// 账号验证
		$this->execute(base64_encode($this->username));
		// 密码验证
		$this->execute(base64_encode($this->password));
	}


	/**
	 * send mail
	 * @subject string
	 * @to array
	 * @cc array
	 * @mails_body string
	 * @attach array
	 * @in_reply_to string
	 * @references string
	 *
	 * attach: file
	 * cc :copy to (mail address)
	 * in_reply_to (The message_id of the email you replied to)
	 * references string (The message_id of the historical reply message)
	 */
	public function send($subject, $to = array(), $cc = array(), $mails_body, $attach = array(), $in_reply_to = '', $references = '')
	{
		if (! is_array($to))
		{
			return array('ack' => 'failure', 'message' => '收件地址需为数组Array');
		}
		// 初始化邮件发送设置
		$this->ini();
		// 发件人
		$this->execute('MAIL FROM:<' . $this->username . ">");
		// 收件人
		foreach ($to as $t)
		{
			$this->execute('RCPT TO:<' . $t . ">");
		}
		// 抄送人
		foreach ($cc as $c)
		{
			$this->execute('RCPT TO:<' . $c . ">");
		}
		// 邮件内容
		$body = 'From:' . $this->username . "\r\n";
		foreach ($to as $t)
		{
			$body .= 'To:' . $t . "\r\n";
		}
		foreach ($cc as $c)
		{
			$body .= 'Cc:' . $c . "\r\n";
		}
		$boundary = 'part_' . uniqid();
		$body .= 'Subject:' . $subject . "\r\n";
		$body .= 'Date:' . date('Y-m-d H:i:s', time()) . "\r\n";
		$body .= empty($in_reply_to) ? '' : ('In-Reply-To:' . $in_reply_to . "\r\n");
		$body .= empty($references) ? '' : ('References:' . $references . "\r\n");
		$body .= 'Content-Type: multipart/mixed; boundary=' . $boundary . "\r\n\r\n";
		$body .= '--' . $boundary . "\r\n";
		$body .= 'Content-Type: text/html; charset=utf-8;' . "\r\n";
		$body .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
		$body .= $mails_body . "\r\n\r\n";
		// 判断是否存在附件
		if ( ! empty($attach))
		{
			foreach ($attach as $a)
			{
				$filename = isset($a['filename']) ? $a['filename'] : 'aaa.txt';
				$content = isset($a['content']) ? $a['content'] : '';
				$body .= '--' . $boundary . "\r\n";
				$body .= 'Content-Type: application/octet-stream;charset=utf-8' . "\r\n";
				$body .= 'Content-Transfer-Encoding: base64' . "\r\n";
				$body .= 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n\r\n";
				$body .= $content . "\r\n\r\n";
			}
		}
		$body .= '--' . $boundary . "--\r\n";
		$body .= "\r\n.";
		$this->execute("DATA");
		$this->execute($body);
		$this->execute("QUIT");
		$result = $this->executeStart();
		return $result;
	}

	private function executeStart()
	{
		try
		{
			if (empty($this->execute_list) || empty($this->stream))
			{
				return array('ack' => 'failure', 'message' => '数据为空');
			}
			foreach ($this->execute_list as $command)
			{
				fwrite($this->stream, $command . "\r\n");
				$handle_status = fread($this->stream, 512);
				if ($this->debug)
				{
					dump($command);
					var_dump($handle_status);
				}
				$status = '/^(5|4)/';
				if(preg_match($status, $handle_status, $matches))
				{
					return array('ack' => 'failure', 'message' => '邮件发送失败：' . $command . "\n" . $handle_status);
				}
				if ($command == 'STARTTLS')
				{
					$crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
					$enable_crypto_result = stream_socket_enable_crypto($this->stream, true, $crypto_method);
					if (! $enable_crypto_result)
					{
						return array('ack' => 'failure', 'message' => 'TLS开启失败');
					}
				}
			}
			return array('ack' => 'success', 'message' => '');
		}
		catch (Exception $ex)
		{
			return array('ack' => 'failure', 'message' => $ex->getMessage());
		}
		catch (Error $er)
		{
			return array('ack' => 'failure', 'message' => $er->getMessage());
		}
	}

	private function execute($command)
	{
		$this->execute_list[] = $command;
	}

	public function debug($flag)
	{
		$this->debug = boolval($flag);
	}
}
