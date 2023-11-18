<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

/**
 *
 * This file is part of the "CyLending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Model
 *         
 */
class ValidationResults
{


    protected $object;


    /**
     *
     * @var array
     */
    protected $infos = array();

    /** @var array  */
    protected $errors = array();


    public function __construct($object = null)
    {
        $this->object = $object;
    }


    /** @return array of srings  */
    public function getInfos()
    {
        return $this->infos;
    }

    /** @return array of srings  */
    public function getErrors()
    {
        return $this->errors;
    }

    /** @return array of srings   */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     *
     * @return array of srings
     */
    public function hasInfos(): bool
    {
        return !empty($this->infos);
    }

    /**
     *
     * @param string $errorKey
     * @param array $arguments
     */
    public function addInfo(string $infoKey, array $arguments = []): void
    {
        $keySplit = explode('.', $infoKey, 2);
        $this->infos['info.' . $infoKey]['arguments'] = $arguments;
        $this->infos['info.' . $infoKey]['id'] = count($keySplit) == 2 ? $keySplit[0] : $infoKey;
    }

    /**
     *
     * @param string $errorKey
     * @param array $arguments
     */
    public function addError(string $errorKey, array $arguments = []): void
    {
        $keySplit = explode('.', $errorKey, 2);
        $this->errors['error.' . $errorKey]['arguments'] = $arguments;
        $this->errors['error.' . $errorKey]['id'] = count($keySplit) == 2 ? $keySplit[0] : $errorKey;
    }

    /**
     *
     * @param
     *            array
     */
    public function addInfos(array $infos)
    {
        foreach ($infos as $info) {
            $this->addInfo($info);
        }
    }

    /**
     *
     * @param
     *            array
     */
    public function addErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

	/**
	 * 
	 * @return object|null
	 */
	public function getObject() {
		return $this->object;
	}
}
