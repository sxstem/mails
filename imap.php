<?php
namespace Yy;
class Imap extends Config
{
	public function getBoxes()
	{
		try
		{
			$result = imap_list($this->stream, '{imap.example.org}', '*');
			foreach ($result as $name)
			{
				$boxes []= imap_mutf7_to_utf8(str_replace('{imap.example.org}', '', $name));
			}
			return $boxes;
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

	public function getUid()
	{
		try
		{
			$search_string = '';
			if (!empty($this->begin_date))
			{
				$search_string .= 'SINCE "' . $this->begin_date . '"';
			}
			if (!empty($this->end_date))
			{
				$search_string .= 'BEFORE "' . $this->end_date . '"';
			}
			$search_string .= 'ALL';
			$uid = imap_search($this->stream, $search_string, SE_UID);
			return $uid;
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

	public function getHeader()
	{
		try
		{
			$uid = $this->getUid();
			if($uid)
			{
				$uid = is_array($uid) ? $uid : array($uid);
				foreach ($uid as $u)
				{
					$msgno = imap_msgno($this->stream, $u);
					$header = imap_header($this->stream, $msgno);
					if ($header)
					{
						$data[] = $this->headerDecode($header, $u);
					}
				}
				return $data;
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

	private function headerDecode($header, $uid)
	{
		try
		{
			if (empty($header->subject))
			{
				$text = '（无主题）';
			}
			else
			{
				$subject = imap_mime_header_decode($header->subject);
				$text = $subject[0]->text;
				if ($subject[0]->charset != 'utf-8' && $subject[0]->charset != 'default')
				{
					//found in mb_list_encodings()
					if (in_array(strtoupper($subject[0]->charset),mb_list_encodings()))
					{
						$text = mb_convert_encoding($text,'UTF-8',$subject[0]->charset);
					}
					else
					{
						//try to convert with iconv()
						$ret = iconv($subject[0]->charset, "UTF-8", $text);
						if ($ret)
						{
							$text =$ret;
						}  //an error occurs (unknown charset)
					}
				}
			}
			$header_info['Subject'] = $text;
			$header_info['Uid'] = $uid;
			$header_info['Date'] = date('Y-m-d H:i:s', (isset($header->Date) ? strtotime($header->Date) : strtotime($header->MailDate)));
			$header_info['FromAddress'] = imap_utf8($header->fromaddress);
			$header_info['ToAddress'] = imap_utf8($header->toaddress);
			$header_info['CcAddress'] = '';
			$header_info['ReplyToAddress'] = isset($header->reply_toaddress) ? imap_utf8($header->reply_toaddress) : '';
			$header_info['SenderAddress'] = isset($header->senderaddress) ? imap_utf8($header->senderaddress) : '';
			$header_info['References'] = isset($header->references) ? imap_utf8($header->references) : '';

			$header_info['InReplyTo'] = '';
			if (isset($header->in_reply_to))
			{
				$reply_subject = imap_mime_header_decode($header->in_reply_to);
				$header_info['InReplyTo'] = $reply_subject[0]->text;
			}

			$header_info['MessageId'] = isset($header->message_id) ? imap_utf8($header->message_id) : '';
			$header_info['Recent'] = trim($header->Recent);
			$header_info['Unseen'] = trim($header->Unseen);
			$header_info['Flagged'] = trim($header->Flagged);
			$header_info['Answered'] = trim($header->Answered);
			$header_info['Deleted'] = trim($header->Deleted);
			$header_info['Draft'] = trim($header->Draft);
			$header_info['Msgno'] = trim($header->Msgno);
			$header_info['Size'] = trim($header->Size);
			if (isset($header->cc))
			{
				foreach ($header->cc as $cc)
				{
					$header_info['CcAddress'] .= isset($cc->personal) ? imap_utf8(trim($cc->personal)) . ' ' : '';
					$header_info['CcAddress'] .= '<' . trim($cc->mailbox) . '@' . trim($cc->host) . '>,';
				}
				$header_info['CcAddress'] = trim($header_info['CcAddress'], ',');
			}
			return $header_info;
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

	public function getBody($msgno, $structure = false, $part_number = false)
	{
		try
		{
			if (!$structure)
			{
				$structure = imap_fetchstructure($this->stream, $msgno);
			}
			if($structure)
			{
				if ($structure->type == 1)
				{
					$data = array();
					foreach ($structure->parts as $number => $parts)
					{
						$number += 1;
						if ($part_number)
						{
							$number = $part_number . '.' . $number;
						}
						$data[]= $this->getBody($msgno, $parts, $number);
					}
					if ($data)
					{
						return $data;
					}
				}
				if (!$part_number)
				{
					$part_number = 1;
				}
				$body = imap_fetchbody($this->stream, $msgno, $part_number);
				if ($structure->encoding == 3)
				{
					$body = imap_base64($body);
				}
				elseif ($structure->encoding == 4)
				{
					$body = imap_qprint($body);
				}
				if (($structure->parameters)[0]->attribute == 'charset')
				{
					$body = iconv(($structure->parameters)[0]->value, 'utf-8//IGNORE', $body);
				}
				$data['part'] = $part_number;
				$data['body'] = base64_encode($body);
				$data['type'] = $this->getBodyType(intval($structure->type));
				$data['content_type'] = $structure->subtype;
				if ($structure->type != 0)
				{
					if (isset($structure->dparameters) && is_array($structure->dparameters))
					{
						foreach ($structure->dparameters as $d)
						{
							if ($d->attribute == 'filename')
							{
								$data['filename'] = imap_utf8($d->value);
							}
						}
					}
					elseif (isset($structure->parameters) && is_array($structure->parameters))
					{
						foreach ($structure->parameters as $d)
						{
							if ($d->attribute == 'name')
							{
								$data['filename'] = imap_utf8($d->value);
							}
						}
					}
				}
				return $data;
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

	private function getBodyType($type)
	{
		$primary_mime_type = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'MODEL', 'OTHER');
		return isset($primary_mime_type[$type]) ? $primary_mime_type[$type] : 'OTHER';
	}

	public function setBeginDate($begin_date)
	{
		$this->begin_date = $begin_date;
	}

	public function setEndDate($end_date)
	{
		$this->end_date = $end_date;
	}
}