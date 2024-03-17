<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Contracts\Translation\TranslatableInterface;

use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CollectionTableType;

/**
 * @author Bruno Martin <bruno.martin.2@gmail.com>
 */
final class CollectionTableField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/collection_table')
            ->setFormType(CollectionTableType::class)
            ->addCssClass('field-collection-table')
            ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-collection-table.js')->onlyOnForms())
            ->setDefaultColumns('col-sm-12 col-md-10')
            ->setCustomOption(CollectionField::OPTION_ALLOW_ADD, true)
            ->setCustomOption(CollectionField::OPTION_ALLOW_DELETE, true)
            ->setCustomOption(CollectionField::OPTION_ENTRY_CRUD_CONTROLLER_FQCN, null);
    }

    public function allowAdd(bool $allow = true): self
    {
        $this->setCustomOption(CollectionField::OPTION_ALLOW_ADD, $allow);

        return $this;
    }

    public function allowDelete(bool $allow = true): self
    {
        $this->setCustomOption(CollectionField::OPTION_ALLOW_DELETE, $allow);

        return $this;
    }

    public function useEntryCrudForm(string $crudControllerFqcn): self
    {
        $this->setCustomOption(CollectionField::OPTION_ENTRY_CRUD_CONTROLLER_FQCN, $crudControllerFqcn);

        return $this;
    }
}
