<?php
/*
 * WellCommerce Open-Source E-Commerce Platform
 *
 * This file is part of the WellCommerce package.
 *
 * (c) Adam Piotrowski <adam@wellcommerce.org>
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */
namespace WellCommerce\Layout\Form;

use WellCommerce\Core\Form\AbstractForm;
use WellCommerce\Core\Form\Builder\FormBuilderInterface;
use WellCommerce\Core\Form\FormInterface;
use WellCommerce\Core\Form\Option;
use WellCommerce\Layout\Model\LayoutBox;

/**
 * Class LayoutBoxForm
 *
 * @package WellCommerce\LayoutBox\Form
 * @author  Adam Piotrowski <adam@wellcommerce.org>
 */
class LayoutBoxForm extends AbstractForm implements FormInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $form = $builder->addForm($options);

        $requiredData = $form->addChild($builder->addFieldset([
            'name'  => 'required_data',
            'label' => $this->trans('Required data')
        ]));

        $languageData = $requiredData->addChild($builder->addFieldsetLanguage([
            'name'      => 'language_data',
            'label'     => $this->trans('Translations'),
            'languages' => $this->getLanguages()
        ]));

        $languageData->addChild($builder->addTextField([
            'name'  => 'name',
            'label' => $this->trans('Name'),
            'rules' => [
                $builder->addRuleRequired($this->trans('Name is required')),
                $builder->addRuleLanguageUnique($this->trans('Name already exists'),
                    [
                        'table'   => 'layout_box_translation',
                        'column'  => 'name',
                        'exclude' => [
                            'column' => 'layout_box_id',
                            'values' => $this->getParam('id')
                        ]
                    ]
                )
            ]
        ]));

        $requiredData->addChild($builder->addTip([
            'tip' => '<p>' . $this->trans('Choose content type for box. Most box types require additional configuration which can be found in Box settings tab.') . '</p>'
        ]));

        $requiredData->addChild($builder->addSelect([
            'name'    => 'type',
            'label'   => $this->trans('Box type'),
            'options' => [],
        ]));

        $requiredData->addChild($builder->addTip([
            'tip' => '<p>' . $this->trans("Enable or disable header rendering in box. Graphical boxes often don't require header.") . '</p>'
        ]));

        $requiredData->addChild($builder->addSelect([
            'name'    => 'show_header',
            'label'   => $this->trans('Show header'),
            'options' => [
                new Option('1', $this->trans('Yes')),
                new Option('0', $this->trans('No'))
            ]
        ]));

        $requiredData->addChild($builder->addTip([
            'tip' => '<p>' . $this->trans('Choose who can see box. You can also hide box from all customers without deleting it.') . '</p>'
        ]));

        $requiredData->AddChild($builder->addSelect([
            'name'    => 'visibility',
            'label'   => $this->trans('Visibility'),
            'options' => [
                new Option('0', 'for all customers'),
                new Option('1', 'only for logged-in customers'),
                new Option('2', 'only for logged-out customers'),
                new Option('3', 'hidden')
            ]
        ]));

        $form->addFilters([
            $builder->addFilterTrim(),
            $builder->addFilterSecure()
        ]);

        return $form;
    }

    /**
     * Prepares form data using retrieved model
     *
     * @param LayoutBox $layoutBox Model
     *
     * @return array
     */
    public function prepareData(LayoutBox $layoutBox)
    {
        $formData     = [];
        $accessor     = $this->getPropertyAccessor();
        $languageData = $layoutBox->translation->getTranslations();

        $accessor->setValue($formData, '[required_data]', [
            'language_data' => $languageData,
            'type'          => $layoutBox->type,
            'show_header'   => $layoutBox->show_header,
            'visibility'    => $layoutBox->visibility,
        ]);

        $accessor->setValue($formData, '[' . $layoutBox->type . ']', $layoutBox->settings);

        return $formData;
    }
}