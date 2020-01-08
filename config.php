<?php
namespace Yy;
class Config
{
	protected $username = '';
	protected $password = '';
	protected $imap = '';
	protected $smtp = '';
	protected $smtp_proxy = '';
	protected $begin_date = '';
	protected $end_date = '';
	protected $mailbox = 'INBOX';
	protected $config = array();
	protected $stream;


	public function setConfig($host)
	{
		switch ($host)
		{
			case '163.com':
				$this->imap = '{imap.163.com:993/imap/ssl}';
				$this->smtp = 'ssl://smtp.163.com';
				$this->smtp_proxy = '465';
				break;
			case 'qq.com':
				$this->imap = '{imap.qq.com:993/imap/ssl}';
				$this->smtp = 'ssl://smtp.qq.com';
				$this->smtp_proxy = '465';
				break;
		}
	}

	public function imapOpen($folder = 'INBOX')
	{
		try
		{
			$folder = imap_utf8_to_mutf7($folder);
			$result = imap_open($this->imap . $folder, $this->username, $this->password);
			$this->stream = $result;
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

	public function imapClose()
	{
		imap_close($this->stream);
	}

	public function setUsername($username)
	{
		$this->username = $username;
		$host = explode('@', $username)[1];
		$this->setConfig($host);
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function smtpOpen()
	{
		try
		{
			$smtp = fsockopen($this->smtp, $this->smtp_proxy);
			$smtp_status = fgets($smtp);
			if (strstr($smtp_status, '220') !== true)
			{
				$this->stream = $smtp;
			}
			else
			{
				throw new Exception('邮件服务器连接失败');
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