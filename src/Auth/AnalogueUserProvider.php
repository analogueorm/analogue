<?php namespace Analogue\ORM\Auth;

use Analogue\ORM\System\Manager;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Illuminate\Hashing\HasherInterface;

class AnalogueUserProvider implements UserProviderInterface {

	/**
	* @var \Illuminate\Hashing\HasherInterface
	*/
	protected $hasher;

	/**
	* @var \Analogue\ORM\System\Manager
	*/
	protected $mapper;

	/**
	* @var string
	*/
	protected $entity;

	/**
	 * @param \Illuminate\Hashing\HasherInterface $hasher  
	 * @param \Analogue\ORM\System\Manager         $manager 
	 * @param string          $entity  
	 */
	public function __construct(HasherInterface $hasher, Manager $manager, $entity)
	{
		$this->hasher = $hasher;
		$this->mapper = $manager->mapper($entity);
		$this->entity = $entity;
	}

	/**
     * Retrieve a user by their unique identifier.
	 *
     * @param  mixed $identifier
     * @return UserInterface|null
     */
    public function retrieveById($identifier)
    {
        return $this->getRepository()->find($identifier);
    }

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
	 *
     * @param  mixed $identifier
     * @param  string $token
     * @return UserInterface|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $entity = $this->getEntity();

        $keyName = $this->mapper->getEntityMap()->getKeyName();

        return $this->getRepository()->where($keyName,'=',$identifier)
        	->where($entity->getRememberTokenName(), '=', $token)->first();
    }

    /**
     * Update the "remember me" token for the given user in storage.
	 *
     * @param  UserInterface $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(UserInterface $user, $token)
    {
        $user->setRememberToken($token);
        
        $this->getRepository()->store($user);
    }

    /**
     * Retrieve a user by the given credentials.
	 *
     * @param  array $credentials
     * @return UserInterface|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $criteria = [];
        foreach ($credentials as $key => $value)
            if ( ! str_contains($key, 'password'))
                $criteria[$key] = $value;

        return $this->getRepository()->firstByAttributes($criteria);
    }

    /**
     * Validate a user against the given credentials.
	 *
     * @param  UserInterface $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(UserInterface $user, array $credentials)
    {
        return $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }

	/**
	* Returns repository for the entity.
	*
	* @return \Analogue\ORM\Repository
	*/
	private function getRepository()
	{
		return $this->mapper->repository($this->entity);
	}

	/**
	 * Instantiate an user entity
	 * 
	 * @return \Analogue\ORM\Entity
	 */
	private function getEntity()
	{
		return $this->mapper->newInstance();
	}
}