<?php

declare(strict_types=1);

use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Data\TemplateData;

it('returns the lowest sequence step as the first step', function () {
    $template = new TemplateData(
        id: 1,
        code: 'MEMBERSHIP',
        name: 'Membership',
        steps: [
            buildStepData(id: 20, code: 'REVIEW', sequenceNo: 2),
            buildStepData(id: 10, code: 'REGISTER', sequenceNo: 1),
        ],
    );

    expect($template->firstStep()?->id)->toBe(10)
        ->and($template->firstStep()?->code)->toBe('REGISTER');
});

it('can find steps by id and code', function () {
    $register = buildStepData(id: 10, code: 'REGISTER', sequenceNo: 1);
    $review = buildStepData(id: 20, code: 'REVIEW', sequenceNo: 2);

    $template = new TemplateData(
        id: 1,
        code: 'MEMBERSHIP',
        name: 'Membership',
        steps: [$register, $review],
    );

    expect($template->findStepById(20))->toBe($review)
        ->and($template->findStepByCode('REGISTER'))->toBe($register)
        ->and($template->findStepById(999))->toBeNull()
        ->and($template->findStepByCode('UNKNOWN'))->toBeNull();
});

it('returns null when a template has no steps', function () {
    $template = new TemplateData(
        id: 1,
        code: 'EMPTY',
        name: 'Empty',
        steps: [],
    );

    expect($template->firstStep())->toBeNull();
});

it('can resolve actions from step data', function () {
    $action = new ActionData(
        templateStepActionId: 1,
        actionId: 2,
        actionCode: 'submit',
        nextTemplateStepId: 3,
        nextStepCode: 'REVIEW',
        completesStep: true,
        closesApplication: false,
        resultingStepStatusId: 4,
        resultingStepStatusCode: 'completed',
        resultingApplicationStatusId: 5,
        resultingApplicationStatusCode: 'pending',
    );

    $step = new StepData(
        id: 10,
        code: 'REGISTER',
        name: 'Register',
        sequenceNo: 1,
        assignmentRoleNames: ['member'],
        actions: ['submit' => $action],
    );

    expect($step->findAction('submit'))->toBe($action)
        ->and($step->findAction('approve'))->toBeNull();
});

function buildStepData(int $id, string $code, int $sequenceNo): StepData
{
    return new StepData(
        id: $id,
        code: $code,
        name: $code,
        sequenceNo: $sequenceNo,
        assignmentRoleNames: [],
        actions: [],
    );
}
