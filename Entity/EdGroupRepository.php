<?php

namespace Explee\EdmodoBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EdGroupRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EdGroupRepository extends EntityRepository
{

    public function findOneByIdAndLicense($edId, $licenseCode)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT e
                 FROM EdmodoBundle:EdGroup e
                 WHERE e.edId = :edId
                 AND e.licenseCode = :licenseCode '
            )
            ->setParameters(array("edId"=> $edId, "licenseCode"=> $licenseCode));
        return $query->getResult();
    }

}
