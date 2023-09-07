<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace M8B\EtherBinder\Misc;

class GethLikeMessage extends AbstractMessage
{
	protected function preProcessMessage(): string
	{
		return chr(0x19)."Ethereum Signed Message:\n"
			.strlen($this->message)
			.$this->message;
	}
}
