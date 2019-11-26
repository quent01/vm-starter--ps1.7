<?php
/**
 * 2007-2018 ETS-Soft
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses. 
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 * 
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 *  @author ETS-Soft <etssoft.jsc@gmail.com>
 *  @copyright  2007-2018 ETS-Soft
 *  @license    Valid for 1 website (or project) for each purchase of license
 *  International Registered Trademark & Property of ETS-Soft
 */

class Customer extends CustomerCore
{
    public function getByEmail($email, $passwd = null, $ignore_guest = true)
    {
        if (!Validate::isEmail($email) || ($passwd && !Validate::isPasswd($passwd))) {
            die(Tools::displayError());
        }
        $result = Db::getInstance()->getRow('
		SELECT *
		FROM `'._DB_PREFIX_.'customer`
		WHERE `email` = \''.pSQL($email).'\'
		'.(isset($passwd) ? 'AND `passwd` = \''.md5(pSQL(_COOKIE_KEY_.$passwd)).'\'' : '').'
		AND `deleted` = 0
		'.($ignore_guest ? ' AND `is_guest` = 0' : ''));
        
        if (!$result) {
            $customer= $result = Db::getInstance()->getRow('
    		SELECT *
    		FROM `'._DB_PREFIX_.'customer`
    		WHERE `email` = \''.pSQL($email).'\'
    		AND `deleted` = 0
    		'.($ignore_guest ? ' AND `is_guest` = 0' : ''));
            if($passwd && $customer && isset($customer['passwd_old_wp']) && $stored_hash= $customer['passwd_old_wp'])
            {
                return $this->CheckPasswordWP($passwd,$stored_hash,$email);
            }
            return false;
        }
        $this->id = $result['id_customer'];
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
    public function CheckPasswordWP($password, $stored_hash,$email)
	{
	   
        if(Tools::strlen($password)<=32)
        {
            if(md5($password)==$stored_hash)
            {
                Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'customer SET passwd="'.md5(pSQL(_COOKIE_KEY_.$password)).'" WHERE email="'.pSQL($email).'"');
                return true;
            }
        }
		if ( Tools::strlen( $password ) > 4096 ) {
			return false;
		}
		$hash = $this->crypt_private($password, $stored_hash);
		if ($hash[0] == '*')
			$hash = crypt($password, $stored_hash);
        
        if($hash === $stored_hash)
        {
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'customer SET passwd="'.md5(pSQL(_COOKIE_KEY_.$password)).'" WHERE email="'.pSQL($email).'"');
            return true;
        }
        else
            return false;
	}
    public function crypt_private($password, $setting)
	{
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$output = '*0';
		if (Tools::substr($setting, 0, 2) == $output)
			$output = '*1';

		$id = Tools::substr($setting, 0, 3);
		# We use "$P$", phpBB3 uses "$H$" for the same thing
		if ($id != '$P$' && $id != '$H$')
			return $output;

		$count_log2 = strpos($itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = Tools::substr($setting, 4, 8);
		if (Tools::strlen($salt) != 8)
			return $output;

		# We're kind of forced to use MD5 here since it's the only
		# cryptographic primitive available in all versions of PHP
		# currently in use.  To implement our own low-level crypto
		# in PHP would result in much worse performance and
		# consequently in lower iteration counts and hashes that are
		# quicker to crack (by non-PHP code).
		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, TRUE);
			do {
				$hash = md5($hash . $password, TRUE);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = Tools::substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);
		return $output;
	}
    public function encode64($input, $count)
	{
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;
			$output .= $itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;
			if ($i < $count)
				$value |= ord($input[$i]) << 16;
			$output .= $itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;
			$output .= $itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}
}