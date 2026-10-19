<?php

namespace App\Rules;

use App\Services\Turnstile;
use Illuminate\Contracts\Validation\Rule;

/**
 * Regra de validação do captcha da Cloudflare.
 *
 * Uso no controller:
 *   'cf-turnstile-response' => ['required', new TurnstileToken($request->ip())]
 *
 * Quando TURNSTILE_ENABLED=false (testes automatizados e desenvolvimento
 * local), a regra deixa passar. Em produção ela TEM que estar ligada.
 */
class TurnstileToken implements Rule
{
    protected ?string $ip;

    public function __construct(?string $ip = null)
    {
        $this->ip = $ip;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (! config('services.turnstile.enabled', true)) {
            return true;
        }

        return app(Turnstile::class)->verify(
            is_string($value) ? $value : null,
            $this->ip
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'A verificação de segurança falhou. Recarregue a página e tente novamente.';
    }
}
