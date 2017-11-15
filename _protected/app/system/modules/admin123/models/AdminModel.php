<?php
/**
 * @author         Pierre-Henry Soria <hello@ph7cms.com>
 * @copyright      (c) 2012-2018, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package        PH7 / App / System / Module / Admin / Inc / Model
 */

namespace PH7;

use PH7\Framework\Mvc\Model\Engine\Db;
use PH7\Framework\Security\Security;

class AdminModel extends AdminCoreModel
{
    /**
     * It recreates an admin method more complicated and more secure than the classic one PH7\UserCoreModel::login()
     *
     * @param string $sEmail
     * @param string $sUsername
     * @param string $sPassword
     *
     * @return boolean Returns TRUE if successful otherwise FALSE
     */
    public function adminLogin($sEmail, $sUsername, $sPassword)
    {
        $rStmt = Db::getInstance()->prepare('SELECT password FROM' .
            Db::prefix('Admins') . 'WHERE email = :email AND username = :username LIMIT 1');
        $rStmt->bindValue(':email', $sEmail, \PDO::PARAM_STR);
        $rStmt->bindValue(':username', $sUsername, \PDO::PARAM_STR);
        $rStmt->execute();
        $oRow = $rStmt->fetch(\PDO::FETCH_OBJ);
        Db::free($rStmt);

        return Security::checkPwd($sPassword, @$oRow->password);
    }

    /**
     * Adding an Admin.
     *
     * @param array $aData
     *
     * @return integer The ID of the Admin.
     */
    public function add(array $aData)
    {
        $sCurrentDate = (new Framework\Date\CDateTime)->get()->dateTime('Y-m-d H:i:s');

        $rStmt = Db::getInstance()->prepare('INSERT INTO' . Db::prefix('Admins') .
            '(email, username, password, firstName, lastName, sex, timeZone, ip, joinDate, lastActivity)
        VALUES (:email, :username, :password, :firstName, :lastName, :sex, :timeZone, :ip, :joinDate, :lastActivity)');
        $rStmt->bindValue(':email', $aData['email'], \PDO::PARAM_STR);
        $rStmt->bindValue(':username', $aData['username'], \PDO::PARAM_STR);
        $rStmt->bindValue(':password', Security::hashPwd($aData['password']), \PDO::PARAM_STR);
        $rStmt->bindValue(':firstName', $aData['first_name'], \PDO::PARAM_STR);
        $rStmt->bindValue(':lastName', $aData['last_name'], \PDO::PARAM_STR);
        $rStmt->bindValue(':sex', $aData['sex'], \PDO::PARAM_STR);
        $rStmt->bindValue(':timeZone', $aData['time_zone'], \PDO::PARAM_STR);
        $rStmt->bindValue(':ip', $aData['ip'], \PDO::PARAM_STR);
        $rStmt->bindValue(':joinDate', $sCurrentDate, \PDO::PARAM_STR);
        $rStmt->bindValue(':lastActivity', $sCurrentDate, \PDO::PARAM_STR);
        $rStmt->execute();
        Db::free($rStmt);
        return Db::getInstance()->lastInsertId();
    }

    /**
     * Delete Admin.
     *
     * @param integer $iProfileId
     * @param string $sUsername
     *
     * @return void
     */
    public function delete($iProfileId, $sUsername)
    {
        $iProfileId = (int)$iProfileId;

        if (AdminCore::isRootProfileId($iProfileId)) {
            exit('You cannot delete the Root Administrator!');
        }

        $oDb = Db::getInstance();
        $oDb->exec('DELETE FROM' . Db::prefix('Admins') . 'WHERE profileId = ' . $iProfileId . ' LIMIT 1');
        unset($oDb);
    }

    /**
     * @param integer|string $mLooking
     * @param boolean $bCount
     * @param string $sOrderBy
     * @param string $iSort
     * @param integer $iOffset
     * @param integer $iLimit
     *
     * @return integer|object
     */
    public function searchAdmin($mLooking, $bCount, $sOrderBy, $iSort, $iOffset, $iLimit)
    {
        $bCount = (bool)$bCount;
        $iOffset = (int)$iOffset;
        $iLimit = (int)$iLimit;
        $mLooking = trim($mLooking);

        $sSqlLimit = (!$bCount) ? ' LIMIT :offset, :limit' : '';
        $sSqlSelect = (!$bCount) ? '*' : 'COUNT(profileId) AS totalUsers';

        if (ctype_digit($mLooking)) {
            $sSqlWhere = ' WHERE profileId = :looking';
        } else {
            $sSqlWhere = ' WHERE username LIKE :looking OR firstName LIKE :looking OR lastName LIKE :looking OR email LIKE :looking OR sex LIKE :looking OR ip LIKE :looking';
        }

        $sSqlOrder = SearchCoreModel::order($sOrderBy, $iSort);

        $rStmt = Db::getInstance()->prepare('SELECT ' . $sSqlSelect . ' FROM' . Db::prefix('Admins') . $sSqlWhere . $sSqlOrder . $sSqlLimit);

        (ctype_digit($mLooking)) ? $rStmt->bindValue(':looking', $mLooking, \PDO::PARAM_INT) : $rStmt->bindValue(':looking', '%' . $mLooking . '%', \PDO::PARAM_STR);

        if (!$bCount) {
            $rStmt->bindParam(':offset', $iOffset, \PDO::PARAM_INT);
            $rStmt->bindParam(':limit', $iLimit, \PDO::PARAM_INT);
        }

        $rStmt->execute();

        if (!$bCount) {
            $mData = $rStmt->fetchAll(\PDO::FETCH_OBJ);
            Db::free($rStmt);
        } else {
            $oRow = $rStmt->fetch(\PDO::FETCH_OBJ);
            Db::free($rStmt);
            $mData = (int)$oRow->totalUsers;
            unset($oRow);
        }

        return $mData;
    }

    /**
     * Update the custom code.
     *
     * @param string $sCode
     * @param string $sType Choose between 'css' and 'js'
     *
     * @return integer|boolean Returns the number of rows on success or FALSE on failure
     */
    public function updateCustomCode($sCode, $sType)
    {
        return $this->orm->update('CustomCode', 'code', $sCode, 'codeType', $sType);
    }
}
