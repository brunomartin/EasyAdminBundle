<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionTableField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use function Symfony\Component\String\u;

/**
 * Duplicated version of CollectionConfigurator to keep the necessary for
 * CollectionTableField class
 * 
 * @author Bruno Martin <bruno.martin.2@gmail.com>
 */
final class CollectionTableConfigurator implements FieldConfiguratorInterface
{
    private RequestStack $requestStack;
    private EntityFactory $entityFactory;
    private ControllerFactory $controllerFactory;

    public function __construct(RequestStack $requestStack, EntityFactory $entityFactory, ControllerFactory $controllerFactory)
    {
        $this->requestStack = $requestStack;
        $this->entityFactory = $entityFactory;
        $this->controllerFactory = $controllerFactory;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CollectionTableField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        // the contents of this field are a collection of other fields, so it cannot be sorted
        $field->setSortable(false);

        $field->setFormTypeOptionIfNotSet('allow_add', $field->getCustomOptions()->get(CollectionField::OPTION_ALLOW_ADD));
        $field->setFormTypeOptionIfNotSet('allow_delete', $field->getCustomOptions()->get(CollectionField::OPTION_ALLOW_DELETE));
        $field->setFormTypeOptionIfNotSet('by_reference', false);
        $field->setFormTypeOptionIfNotSet('delete_empty', true);
        $field->setFormTypeOptionIfNotSet('prototype_name', '__'.u($field->getProperty())->replace('.', '_').'name__');

        $field->setFormattedValue($this->formatCollection($field, $context));

        if (!$entityDto->isAssociation($field->getProperty())) {
            throw new \RuntimeException(sprintf('The "%s" collection field of "%s" cannot use the "useEntryCrudForm()" method because it is not a Doctrine association.', $field->getProperty(), $context->getCrud()?->getControllerFqcn()));
        }

        $field->setFormTypeOption('entry_type', CrudFormType::class);

        $targetEntityFqcn = $field->getDoctrineMetadata()->get('targetEntity');

        // ColletionTableField only works with EasyAdmin CRUD Form
        $targetCrudControllerFqcn = $field->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_CONTROLLER_FQCN);
        if (null === $targetCrudControllerFqcn) {
            throw new \RuntimeException(sprintf('The "%s" collection field of "%s" shall use the "useEntryCrudForm()" method with the correct EasyAdmin CRUD controller.', $field->getProperty(), $context->getCrud()?->getControllerFqcn()));
        }

        $editEntityDto = $this->createEntityDto($targetEntityFqcn, $targetCrudControllerFqcn, Action::EDIT, Crud::PAGE_EDIT);
        $field->setFormTypeOption('entry_options.entityDto', $editEntityDto);

        $newEntityDto = $this->createEntityDto($targetEntityFqcn, $targetCrudControllerFqcn, Action::NEW, Crud::PAGE_NEW);

        try {
            $field->setFormTypeOption('prototype_options.entityDto', $newEntityDto);
        } catch (UndefinedOptionsException $exception) {
            throw new \RuntimeException(sprintf('The "%s" collection field of "%s" uses the "useEntryCrudForm()" method, which requires Symfony 6.1 or newer to work. Upgrade your Symfony version.', $field->getProperty(), $context->getCrud()?->getControllerFqcn()), 0, $exception);
        }
    }

    private function formatCollection(FieldDto $field, AdminContext $context)
    {
        $doctrineMetadata = $field->getDoctrineMetadata();
        if ('array' !== $doctrineMetadata->get('type') && !$field->getValue() instanceof PersistentCollection) {
            return $this->countNumElements($field->getValue());
        }

        $collectionItemsAsText = [];
        foreach ($field->getValue() ?? [] as $item) {
            if (!\is_string($item) && !(\is_object($item) && method_exists($item, '__toString'))) {
                return $this->countNumElements($field->getValue());
            }

            $collectionItemsAsText[] = (string) $item;
        }

        $isDetailAction = Action::DETAIL === $context->getCrud()->getCurrentAction();

        return u(', ')->join($collectionItemsAsText)->truncate($isDetailAction ? 512 : 32, '…')->toString();
    }

    private function countNumElements($collection): int
    {
        if (null === $collection) {
            return 0;
        }

        if (is_countable($collection)) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }

    private function createEntityDto(string $targetEntityFqcn, string $targetCrudControllerFqcn, string $crudAction, string $pageName): EntityDto
    {
        $entityDto = $this->entityFactory->create($targetEntityFqcn);

        $crudController = $this->controllerFactory->getCrudControllerInstance(
            $targetCrudControllerFqcn,
            $crudAction,
            $this->requestStack->getMainRequest()
        );

        $fields = $crudController->configureFields($pageName);

        $this->entityFactory->processFields($entityDto, FieldCollection::new($fields));

        return $entityDto;
    }
}
