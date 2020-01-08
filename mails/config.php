<?php
namespace Yy;
class Config
{
	/**
	 * username
	 * @var string
	 */
	protected $username = '';

	/**
	 * password
	 * @var string
	 */
	protected $password = '';

	/**
	 * mail box
	 * @var string
	 */
	protected $folder = '';

	/**
	 * imap url
	 * @var string
	 */
	protected $imap = '';

	/**
	 * smtp url
	 * @var string
	 */
	protected $smtp = '';

	/**
	 * smtp proxy
	 * @var string
	 */
	protected $smtp_proxy = '';

	/**
	 * begin date
	 * @var date
	 */
	protected $begin_date = '';

	/**
	 * end date
	 * @var date
	 */
	protected $end_date = '';

	/**
	 * Connected mailbox resources
	 * @var resources
	 */
	protected $stream;

	/**
	 * Connect imap
	 * @username string
	 * @password string
	 * @folder string
	 */
	public function __construct($username, $password ,$folder = 'INBOX')
	{


		//设置用户名
		$this->username = $username;

		//设置邮箱类型url：163，QQ等
		$host = explode('@', $username)[1];
		$this->setConfig($host);

		//设置密码
		$this->password = $password;

		//设置连接的邮箱，收件箱、发件箱等
		//打开邮箱流
		$this->setFolder($folder);
	}

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

	/**
	 * reconnect imap
	 * @folder string
	 */
	public function setFolder($folder)
	{
		if (!empty($this->folder))
		{
			$this->imapClose();
		}
		$this->folder = imap_utf8_to_mutf7($folder);
		$this->imapOpen();
	}

	public function imapOpen()
	{
		try
		{
			//打开资源流并保存
			$this->stream = imap_open($this->imap . $this->folder, $this->username, $this->password);
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

	/**
	 * close imap
	 */
	public function imapClose()
	{
		imap_close($this->stream);
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