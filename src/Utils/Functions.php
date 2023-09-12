<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace M8B\EtherBinder\Utils;

use M8B\EtherBinder\Common\Block;
use M8B\EtherBinder\Exceptions\InvalidHexException;
use M8B\EtherBinder\Exceptions\InvalidHexLengthException;
use M8B\EtherBinder\Misc\EIP1559Config;

/**
 * Functions is an abstract utility class that holds static utility methods.
 *
 * @author DubbaThony
 */
abstract class Functions {

	/**
	 * Validates the length of a hexadecimal string. In case the length is invalid, exception is risen.
	 *
	 * @param string $hex The hex string.
	 * @param int $len The expected length.
	 * @throws InvalidHexException
	 * @throws InvalidHexLengthException
	 */
	public static function mustHexLen(string $hex, int $len): void
	{
		if(str_starts_with($hex, "0x")){
			$hex = substr($hex, 2);
		}

		if(!ctype_xdigit($hex))
			throw new InvalidHexException("got unexpected character in hex");
		if(strlen($hex) != $len)
			throw new InvalidHexLengthException($len, strlen($hex));
	}

	/**
	 * Left-pads a hex string to a specific length.
	 *
	 * @param string $hex The hex string.
	 * @param int $padTo The length to pad to.
	 * @param bool $closestMultiplier Whether to pad to the closest multiple of $padTo, instead of just to $padTo
	 * @return string The padded hex string.
	 */
	public static function lPadHex(string $hex, int $padTo, bool $closestMultiplier = true): string
	{
		$has0x = false;
		if(str_starts_with($hex, "0x")) {
			$hex = substr($hex, 2);
			$has0x = true;
		}

		if(strlen($hex) > $padTo && !$closestMultiplier) {
			return ($has0x ? "0x":"") . $hex;
		}

		if($closestMultiplier) {
			$targetLength = ceil(strlen($hex) / $padTo) * $padTo;
		} else {
			$targetLength = $padTo;
		}

		$missingZeroes = $targetLength - strlen($hex);
		$paddedHex = str_repeat('0', $missingZeroes) . $hex;
		return ($has0x ? "0x" : "") . $paddedHex;
	}

	/**
	 * Converts an integer to a hex string.
	 *
	 * @param int $val The integer value.
	 * @param bool $with0x Whether to include the "0x" prefix.
	 * @return string The hex string.
	 */
	public static function int2hex(int $val, bool $with0x = true): string
	{
		return ($with0x ? "0x" : "").dechex($val);
	}

	/**
	 * Calculates the base fee for the next block in an EIP1559 compatible chain.
	 *
	 * @param Block $previous The previous block.
	 * @param EIP1559Config $config The EIP1559 configuration. Only required field is $config->activationBlockNumber
	 * @return OOGmp The calculated base fee.
	 */
	public static function GetNextBlockBaseFee(Block $previous, EIP1559Config $config): OOGmp
	{
		if($previous->number <= $config->activationBlockNumber) {
			return new OOGmp(EIP1559Config::INITIAL_BASE_FEE);
		}

		if(!$previous->isEIP1559()) {
			return new OOGmp(EIP1559Config::INITIAL_BASE_FEE);
		}

		$parentGasTarget = $previous->gasLimit / EIP1559Config::ELASTICITY_MULTIPLIER;
		if($parentGasTarget == $previous->gasUsed) {
			return $previous->baseFeePerGas;
		}

		if($previous->gasUsed > $parentGasTarget) {
			return (new OOGmp($previous->gasUsed - $parentGasTarget))
				->mul($previous->baseFeePerGas)
				->div($parentGasTarget)
				->div(EIP1559Config::BASE_FEE_CHANGE_DENOMINATOR)
				->max(1)
				->add($previous->baseFeePerGas);
		} else {
			return $previous->baseFeePerGas
				->sub(
					(new OOGmp($parentGasTarget - $previous->gasUsed))
					->mul($previous->baseFeePerGas)
					->div($parentGasTarget)
					->div(EIP1559Config::BASE_FEE_CHANGE_DENOMINATOR)
				)->max(0);
		}
	}
}
