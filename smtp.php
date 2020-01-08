<?php
namespace Yy;
class Smtp extends Config
{
	private function ini()
	{
		// 握手
		$this->execute($this->stream, "HELO {$this->smtp}\r\n", '250');
		// 登录验证
		$this->execute($this->stream, "AUTH LOGIN\r\n", '334');
		// 账号验证
		$this->execute($this->stream,  base64_encode($this->username) . "\r\n", '334');
		// 密码验证
		$this->execute($this->stream,  base64_encode($this->password) . "\r\n", '235');
	}

	public function send($subject, $to = array(), $cc = array(), $mails_body, $in_reply_to, $references, $attach)
	{
		// 初始化邮件发送设置
		$this->ini();
		// 发件人
		$this->execute($this->stream, 'MAIL FROM:<' . $this->username . ">\r\n", '250');
		// 收件人
		foreach ($to as $t)
		{
			$this->execute($this->stream, 'RCPT TO:<' . $t . ">\r\n", '250');
		}
		// 抄送人
		foreach ($cc as $c)
		{
			$this->execute($this->stream, 'RCPT TO:<' . $c . ">\r\n", '250');
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
		$body .= 'In-Reply-To:' . $in_reply_to . "\r\n";
		$body .= 'References:' . $references . "\r\n";
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
		$body .= "\r\n.\r\n";
		$this->execute($this->stream, "DATA\r\n", '354');
		$this->execute($this->stream, $body, '250');
		$this->execute($this->stream, "QUIT\r\n", '221');
	}

	private function execute($handle, $command, $status)
	{
		try
		{
			fwrite($handle, $command);
			$handle_status = fgets($handle);
			if (strstr($handle_status, $status) === false)
			{
				throw new Exception('邮件发送失败：' . $status . ', ' . $command);
			}
		}
		catch (Exception $ex)
		{
			throw new Exception($ex->getMessage());
		}
		catch (Error $er)
		{
			throw new Error($er->getMessage());
		}
	}
}