<?php
declare(strict_types=1);
namespace Hyperf\Apidog\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ApiResponse extends AbstractAnnotation
{
    public $code;

    public $description;

    public $schema;

    public $template;

    public function __construct($value = null)
    {
        parent::__construct($value);
        if (is_array($this->description)) {
            $this->description = json_encode($this->description, JSON_UNESCAPED_UNICODE);
        }
        $this->makeSchema();
    }

    public function makeSchema()
    {
    }
}
