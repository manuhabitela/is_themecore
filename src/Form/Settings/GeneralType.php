<?php


declare(strict_types=1);

namespace Oksydan\Module\IsThemeCore\Form\Settings;

use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class GeneralType extends TranslatorAwareType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $displayListChoices;

    /**
     * GeneralType constructor.
     *
     * @param TranslatorInterface $translator
     * @param array $locales
     * @param array $displayListChoices
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        array $displayListChoices
    ) {
        parent::__construct($translator, $locales);
        $this->displayListChoices = $displayListChoices;
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('list_display_settings',
                ChoiceType::class,
                [
                    'choices' => $this->displayListChoices,
                    'label' => $this->trans('Default list display', 'Modules.isthemecore.Admin'),
                    'multistore_configuration_key' => GeneralConfiguration::THEMECORE_DISPLAY_LIST,
                ]
            )
            ->add('early_hints',
                SwitchType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Early hints (HTTP 103) enabled', 'Modules.isthemecore.Admin'),
                    'help' => $this->trans('Cloudflare CDN, Early hints option have to enabled. <a href="https://developers.cloudflare.com/cache/about/early-hints/">More information</a>', 'Modules.isthemecore.Admin'),
                ]
            )
            ->add('preload_css',
                SwitchType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Preload css enabled, only working with CCC for css option enabled', 'Modules.isthemecore.Admin'),
                ]
            )
            ->add('cloudflare_images',
                SwitchType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Use Cloudflare for images', 'Modules.isthemecore.Admin'),
                    'help' => $this->trans('⚠️ You must clear the cache in Performances settings after changing this', 'Modules.isthemecore.Admin'),
                ]
            )
            ->add('cloudflare_resized_images',
                SwitchType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Send \'cloudflare_default\' images to Cloudflare instead of original images', 'Modules.isthemecore.Admin'),
                    'help' => $this->trans('Faster image loading when not hitting Cloudflare cache, at the cost of worse image quality', 'Modules.isthemecore.Admin'),
                ]
            )
            ->add('cloudflare_zone',
                TextType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Cloudflare zone', 'Modules.isthemecore.Admin'),
                ]
            )
            ->add(
                'collection_feature_id',
                TextType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Collection feature ID', 'Modules.isthemecore.Admin'),
                    'help' => $this->trans('This feature will be used to display products of the same collection on the product page', 'Modules.isthemecore.Admin'),
                ]
            )
            ->add(
                'collab_category_id',
                TextType::class,
                [
                    'required' => false,
                    'label' => $this->trans('Collaboration category ID', 'Modules.isthemecore.Admin'),
                    'help' => $this->trans('Products from this category will be displayed with the collab image', 'Modules.isthemecore.Admin'),
                ]
            );
    }

    /**
     * {@inheritdoc}
     *
     * @see MultistoreConfigurationTypeExtension
     */
    public function getParent(): string
    {
        return MultistoreConfigurationType::class;
    }
}
