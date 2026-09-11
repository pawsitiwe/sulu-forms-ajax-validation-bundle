<?php

declare(strict_types=1);

namespace Pawsitiwe\Controller;

use Sulu\Bundle\FormBundle\Configuration\FormConfigurationFactory;
use Sulu\Bundle\FormBundle\Entity\Dynamic;
use Sulu\Bundle\FormBundle\Form\BuilderInterface;
use Sulu\Bundle\FormBundle\Form\HandlerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ValidationController extends AbstractController
{
    private BuilderInterface $formBuilder;
    private HandlerInterface $formHandler;
    private FormConfigurationFactory $formConfigurationFactory;
    private RequestAnalyzerInterface $requestAnalyzer;
    private LocaleAwareInterface $translator;

    public function __construct(
        BuilderInterface $formBuilder,
        HandlerInterface $formHandler,
        FormConfigurationFactory $formConfigurationFactory,
        RequestAnalyzerInterface $requestAnalyzer,
        LocaleAwareInterface $translator
    ) {
        $this->formBuilder = $formBuilder;
        $this->formHandler = $formHandler;
        $this->formConfigurationFactory = $formConfigurationFactory;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->translator = $translator;
    }

    public function validateFormFields(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        if (!isset($requestData['fields']) || !is_array($requestData['fields'])) {
            return new JsonResponse(['error' => 'Invalid JSON structure'], 400);
        }

        $formData = [];
        foreach ($requestData['fields'] as $field) {
            if (!isset($field['id'], $field['value'])) {
                continue;
            }

            $parts = explode('_', $field['id'], 3);
            $fieldName = $parts[2] ?? $field['id'];

            $formData[$fieldName] = $field['value'];
        }

        if (empty($formData)) {
            return new JsonResponse(['error' => 'No form data found'], 400);
        }

        // ValidationRequestLocaleListener (kernel.request, runs before the
        // controller) already re-resolves webspace/locale from the Referer
        // and overwrites the request's Sulu attributes with it, so this
        // reads the correct (non-default) locale directly.
        $previousLocale = $this->translator->getLocale();

        try {
            $locale = $this->requestAnalyzer->getCurrentLocalization()?->getLocale() ?? $formData['locale'] ?? null;

            if (null === $locale) {
                return new JsonResponse(['error' => 'Unable to resolve locale'], 400);
            }

            // The validator translates constraint violation messages using
            // the translator's locale at the moment $form->submit() runs
            // below, not the request's locale, so it must be set explicitly.
            $this->translator->setLocale($locale);

            $form = $this->formBuilder->build(intval($formData["formId"]), $formData["type"], $formData["typeId"], $locale, $formData["formName"]);
            if (!$form instanceof FormInterface) {
                return new JsonResponse(['message' => 'No form data found'], 400);
            }

            $form->submit($formData);

            $i = 0;
            $fieldsInformation = [];

            foreach ($form->all() as $field) {
                $fieldName = $field->getName();
                if (
                    $fieldName === 'checksum' ||
                    $fieldName === 'submit' ||
                    strpos($fieldName, 'headline') !== false ||
                    strpos($fieldName, 'freeText') !== false ||
                    strpos($fieldName, 'spacer') !== false
                ) {
                    continue;
                }

                $fieldsInformation[] = $this->getFieldInformation($field, $field->isValid(), $requestData["fields"][$i]["modified"]);
                $i++;
            }

            $formValidity = array_reduce($fieldsInformation, fn($carry, $field) => $carry && $field['valid'], true);

            if ($formValidity && $requestData['send']) {
                /** @var Dynamic $formEntity */
                $formEntity = $form->getData();

                $configuration = $this->formConfigurationFactory->buildByDynamic($formEntity);
                $this->formHandler->handle($form, $configuration);
            }

            return new JsonResponse([
                'message' => "",
                'valid' => $formValidity,
                'fields' => $fieldsInformation
            ], 200);
        } finally {
            $this->translator->setLocale($previousLocale);
        }
    }


    private function getFieldInformation(FormInterface $field, bool $validity, bool $modified): array
    {
        $errors = $field->getErrors();
        $errorMessages = '';
        foreach ($errors as $error) {
            $errorMessages .= $error->getMessage();
        }

        $fieldData = [
            'id' => $field->getParent()->getName() . '_' . $field->getName(),
            'valid' => $validity,
            'modified' => $modified
        ];

        if (!empty($errorMessages)) {
            $fieldData['violation']['message'] = $errorMessages;
        }

        return $fieldData;
    }
}
