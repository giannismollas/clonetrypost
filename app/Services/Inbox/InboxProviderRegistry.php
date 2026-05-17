<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\SocialAccount\Platform;
use InvalidArgumentException;

class InboxProviderRegistry
{
    /** @var array<string, InboxProvider> */
    private array $providers = [];

    /**
     * @param  iterable<InboxProvider>  $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->platform()->value] = $provider;
        }
    }

    public function for(Platform $platform): InboxProvider
    {
        if (! isset($this->providers[$platform->value])) {
            throw new InvalidArgumentException("No inbox provider registered for platform [{$platform->value}].");
        }

        return $this->providers[$platform->value];
    }

    public function supports(Platform $platform): bool
    {
        return isset($this->providers[$platform->value]);
    }
}
