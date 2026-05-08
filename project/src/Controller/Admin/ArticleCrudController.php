<?php

namespace App\Controller\Admin;

use App\Entity\Blog\Article;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setSearchFields(['title', 'content', 'metaTitle'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextareaField::new('excerpt', 'Extrait')
            ->setHelp('Résumé court de l\'article (optionnel)');

        // Champ CKEditor pour le contenu HTML
        yield \EasyCorp\Bundle\EasyAdminBundle\Field\Field::new('content', 'Contenu')
            ->setFormType(CKEditorType::class)
            ->setFormTypeOptions([
                'config' => [
                    'toolbar' => 'full',
                    'extraAllowedContent' => 'iframe[*]',
                    'allowedContent' => true,
                ],
            ])
            ->hideOnIndex();

        yield AssociationField::new('category', 'Catégorie');
        yield ImageField::new('featuredImage', 'Image mise en avant')
            ->setBasePath('uploads/blog/')
            ->setUploadDir('public/uploads/blog/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->hideOnIndex();

        yield TextField::new('metaTitle', 'Meta Titre (SEO)')
            ->hideOnIndex()
            ->setHelp('Titre pour les moteurs de recherche (60 caractères max)');
        yield TextareaField::new('metaDescription', 'Meta Description (SEO)')
            ->hideOnIndex()
            ->setHelp('Description pour les moteurs de recherche (160 caractères max)');

        yield BooleanField::new('published', 'Publié');
        yield DateTimeField::new('publishedAt', 'Date de publication')
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('published'))
            ->add(EntityFilter::new('category'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->remove(Crud::PAGE_INDEX, Action::BATCH_DELETE);
    }
}
