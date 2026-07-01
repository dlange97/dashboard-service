<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Topic;

final class JobOffersTopic extends AbstractWishTopic
{
    public function key(): string
    {
        return 'job-offers';
    }

    public function label(): string
    {
        return 'Job offers';
    }

    public function icon(): string
    {
        return '💼';
    }

    public function fields(): array
    {
        return [
            ['name' => 'role', 'label' => 'Role / keywords', 'type' => 'text', 'placeholder' => 'e.g. PHP developer'],
            ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'placeholder' => 'e.g. Remote, Warsaw'],
            ['name' => 'seniority', 'label' => 'Seniority', 'type' => 'select', 'options' => ['intern', 'junior', 'mid', 'senior', 'lead']],
            ['name' => 'employmentType', 'label' => 'Employment type', 'type' => 'select', 'options' => ['full-time', 'part-time', 'contract', 'b2b']],
            ['name' => 'remote', 'label' => 'Remote', 'type' => 'select', 'options' => ['onsite', 'hybrid', 'remote']],
            ['name' => 'salaryMin', 'label' => 'Min salary', 'type' => 'number', 'unit' => 'EUR/month'],
        ];
    }

    public function buildInstruction(array $criteria, int $limit): string
    {
        return sprintf(
            'Search the web for up to %d currently open job offers matching these criteria: %s. '
            . 'For every offer return: title, company, salary, employmentType, location, url (direct link to the posting), '
            . 'description (one short sentence) and source (job board name).',
            $limit,
            $this->describeCriteria($criteria),
        );
    }

    protected function resultKeys(): array
    {
        return ['title', 'company', 'salary', 'employmentType', 'location', 'url', 'description', 'source'];
    }
}
