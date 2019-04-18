<?php
declare(strict_types = 1);

namespace Astrocasts\Stocker\Serialization;

use Assert\Assertion;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;

final class JsonSerializer
{
    private $contextFactory;
    private $docblockFactory;
    private $typeResolver;

    public function __construct()
    {
        $this->contextFactory = new ContextFactory();
        $this->docblockFactory = DocBlockFactory::createInstance();
        $this->typeResolver = new TypeResolver();
    }

    public function deserialize(string $type, string $jsonEncodedData)
    {
        $resolvedType = $this->typeResolver->resolve($type);

        return self::restoreDataStructure($resolvedType, $this->jsonDecode($jsonEncodedData));
    }

    private function restoreDataStructure(Type $type, $data)
    {
        if ($data === null) {
            // TODO verify that null is allowed
            return null;
        }
        if ($type instanceof String_) {
            return (string)$data;
        }
        if ($type instanceof Integer) {
            return (integer)$data;
        }
        if ($type instanceof Boolean) {
            return (boolean)$data;
        }
        if ($type instanceof Float_) {
            return (float)$data;
        }

        if ($type instanceof Object_) {
            $reflection = new \ReflectionClass((string)$type);
            if (!$reflection->isUserDefined()) {
                throw new \LogicException(sprintf('Class "%s" is not user-defined', $type));
            }

            $properties = $reflection->getProperties();

            $object = $reflection->newInstanceWithoutConstructor();

            if ($reflection->implementsInterface(CollapseToSingleValue::class)) {
                $property = $properties[0];
                $propertyType = $this->resolvePropertyType($property, $reflection);
                $property->setAccessible(true);
                $property->setValue($object, self::restoreDataStructure($propertyType, $data));

                return $object;
            }

            Assertion::isArray($data);

            foreach ($properties as $property) {
                if (!array_key_exists($property->getName(), $data)) {
                    continue;
                }

                $propertyType = $this->resolvePropertyType($property, $reflection);
                $property->setAccessible(true);
                $property->setValue($object, self::restoreDataStructure($propertyType, $data[$property->getName()]));
            }
            return $object;
        }

        if ($type instanceof Array_) {
            $processed = [];
            foreach ($data as $key => $elementData) {
                $processed[$key] = self::restoreDataStructure($type->getValueType(), $elementData);
            }

            return $processed;
        }

        throw new \LogicException('Unsupported type: ' . get_class($type));
    }

    public function serialize($rawData)
    {
        return json_encode($this->extractSerializableDataFrom($rawData), JSON_PRETTY_PRINT);
    }

    private function extractSerializableDataFrom($something)
    {
        if (is_object($something)) {
            $reflection = new \ReflectionClass(get_class($something));
            if (!$reflection->isUserDefined()) {
                throw new \LogicException(sprintf('Class "%s" is not user-defined', $reflection->getName()));
            }

            $properties = $reflection->getProperties();

            if ($reflection->implementsInterface(CollapseToSingleValue::class)) {
                $property = $properties[0];
                $property->setAccessible(true);
                return $this->extractSerializableDataFrom($property->getValue($something));
            }

            $data = [];

            foreach ($properties as $property) {
                $property->setAccessible(true);
                $data[$property->getName()] = $this->extractSerializableDataFrom($property->getValue($something));
            }

            return $data;
        }

        if (is_array($something)) {
            $data = [];
            foreach ($something as $key => $element) {
                $data[$key] = $this->extractSerializableDataFrom($element);
            }

            return $data;
        }

        if (is_scalar($something) || $something === null) {
            return $something;
        }

        throw new \LogicException(sprintf(
            'Unsupported type: "%s" (%s). You can only serialize objects, arrays and scalar values.',
            gettype($something),
            var_export($something, true)
        ));
    }

    private function resolvePropertyType(\ReflectionProperty $property, \ReflectionClass $class) : Type
    {
        $fileName = $class->getFileName();
        Assertion::file($fileName, sprintf(
            'Class "%s" has no source file, maybe it is a PHP built-in class?',
            $class->getName()
        ));
        $context = $this->contextFactory->createForNamespace(
            $class->getNamespaceName(),
            file_get_contents($fileName)
        );

        $docComment = $property->getDocComment();
        Assertion::notEmpty($docComment, sprintf('You need to add a docblock to property "%s"', $property->getName()));

        $docblock = $this->docblockFactory->create($docComment, $context);
        $varTags = $docblock->getTagsByName('var');
        Assertion::count(
            $varTags,
            1,
            sprintf('You need to add an @var annotation to property "%s"', $property->getName())
        );
        /** @var Var_[] $varTags */
        $propertyType = $varTags[0]->getType();

        return $propertyType;
    }

    private function jsonDecode(string $jsonEncodedData) : array
    {
        $decoded = json_decode($jsonEncodedData, true);
        if ($decoded === null && json_last_error()) {
            throw new \LogicException('You provided invalid JSON: ' . json_last_error_msg());
        }

        if (is_string($decoded)) {
            throw new \LogicException('You cannot serialize a top-level object that implements ' . CollapseToSingleValue::class);
        }

        return $decoded;
    }
}
