<?php

namespace App\Http\Controllers;

use Closure;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Throwable;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Run a domain mutation and return a consistent, safe flash response.
     */
    protected function mutate(
        Closure $operation,
        string $successMessage,
        string $redirectRoute,
    ): RedirectResponse {
        try {
            $operation();
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'The requested change could not be completed. Please try again.');
        }

        return to_route($redirectRoute)->with([
            'success' => $successMessage,
            'message' => $successMessage,
        ]);
    }
}
