<?php

declare(strict_types=1);

namespace Pawsitiwe\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\FormBundle\Form\BuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ValidationController extends AbstractController
{
    private BuilderInterface $formBuilder;
    private EntityManagerInterface $em;

    public function __construct(BuilderInterface $formBuilder, EntityManagerInterface $em)
    {
        $this->formBuilder = $formBuilder;
        $this->em = $em;
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

        $form = $this->formBuilder->build(intval($formData["formId"]), $formData["type"], $formData["typeId"], $formData["locale"], $formData["formName"]);
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
            $formEntity = $form->getData();

            $this->em->persist($formEntity);
            $this->em->flush();
        }

        return new JsonResponse([
            'message' => "",
            'valid' => $formValidity,
            'fields' => $fieldsInformation
        ], 200);
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
