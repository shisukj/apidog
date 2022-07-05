<?php
declare(strict_types=1);
namespace Hyperf\Apidog\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

abstract class Param extends AbstractAnnotation
{
    public $in;

    public $key;

    public $rule;

    public $default;

    public $description;

    public function __construct($value = null)
    {
        parent::__construct($value);
        $this->setName()->setDescription()->setRequire()->setType();
    }

    public function setName()
    {
        $this->name = explode('|', $this->key)[0];

        return $this;
    }

    public function setDescription()
    {
        $this->description = $this->description ?: explode('|', $this->key)[1] ?? '';

        return $this;
    }

    public function setRequire()
    {
        $this->required = in_array("required", explode('|', $this->rule));

        return $this;
    }

    public function setType()
    {
        $type = 'string';
        if (strpos($this->rule, 'int') !== false) {
            $type = 'integer';
        }
        $this->type = $type;

        return $this;
    }
}
