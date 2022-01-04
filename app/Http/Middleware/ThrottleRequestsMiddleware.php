<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequestsMiddleware extends \Illuminate\Routing\Middleware\ThrottleRequests {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);

        $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildException($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
    /**
     * Create a 'too many attempts' response.
     *
     * @param  string $key
     * @param  int $maxAttempts
     * @return \Illuminate\Http\Response
     */
    protected function buildException($key, $maxAttempts)
    {
        $message = json_encode(['error' => 'Too many attempts, please slow down the request.']);
        
        $response = new Response($message, 429);

        $retryAfter = $this->getTimeUntilNextRetry($key);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }
}
