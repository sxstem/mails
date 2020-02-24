<?php
namespace Sxstem\Mails;
class Mark extends Config
{
	/**
	 * mark read
	 * @uid string
	 */
	public function set_seen($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Seen", ST_UID );
		return $result;
	}

	/**
	 * mark udread
	 * @uid string
	 */
	public function clear_seen($uid)
	{
		$result = imap_clearflag_full($this->stream, $uid, "\\Seen", ST_UID );
		return $result;
	}

	/**
	 * mark flag
	 * @uid string
	 */
	public function set_flagged($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Flagged", ST_UID );
		return $result;
	}

	/**
	 * clear flag
	 * @uid string
	 */
	public function clear_flagged($uid)
	{
		$result = imap_clearflag_full($this->stream, $uid, "\\Flagged", ST_UID );
		return $result;
	}

	/**
	 * delete mail
	 * @uid string
	 */
	public function set_deleted($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Deleted", ST_UID );
		if ($result)
		{
			imap_expunge($this->stream);
		}
		return $result;
	}

	/**
	 * mark answered
	 * @uid string
	 */
	public function set_answered($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Answered", ST_UID );
		return $result;
	}

	/**
	 * move mail to folder
	 * @uid string
	 * @folder string
	 */
	public function move_mail($uid, $folder)
	{
		$result = imap_mail_move($this->stream, $uid, imap_utf8_to_mutf7($folder),CP_UID);
		if ($result)
		{
			imap_expunge($this->stream);
		}
		return $result;
	}

}
