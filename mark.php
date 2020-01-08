<?php
namespace Yy;
class Mark extends \Yy\Config
{
	public function set_seen($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Seen", ST_UID );
		return $result;
	}

	public function clear_seen($uid)
	{
		$result = imap_clearflag_full($this->stream, $uid, "\\Seen", ST_UID );
		return $result;
	}

	public function set_flagged($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Flagged", ST_UID );
		return $result;
	}

	public function clear_flagged($uid)
	{
		$result = imap_clearflag_full($this->stream, $uid, "\\Flagged", ST_UID );
		return $result;
	}

	public function set_deleted($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Deleted", ST_UID );
		if ($result)
		{
			imap_expunge($this->stream);
		}
		return $result;
	}

	public function set_answered($uid)
	{
		$result = imap_setflag_full($this->stream, $uid, "\\Answered", ST_UID );
		return $result;
	}

	public function move_mail($uids, $folder)
	{
		$result = imap_mail_move($this->stream, $uids, imap_utf8_to_mutf7($folder),CP_UID);
		if ($result)
		{
			imap_expunge($this->stream);
		}
		return $result;
	}

}