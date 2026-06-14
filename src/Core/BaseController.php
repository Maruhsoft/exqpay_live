<?php
/**
 * Base Controller
 */

namespace Exqpay\Core;

use Exqpay\Core\Validation\Validator;
use Exqpay\Core\Exception\ValidationException;

class BaseController
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request = null, Response $response = null)
    {
        $this->request = $request ?? Request::createFromGlobals();
        $this->response = $response ?? new Response();
    }

    /**
     * Validate request input
     */
    protected function validate(array $rules, array $messages = []): array
    {
        $validator = new Validator($this->request->all(), $rules, $messages);

        if (!$validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        return $this->request->all();
    }

    /**
     * Send success response
     */
    protected function success(array $data = [], string $message = 'Success'): Response
    {
        return $this->response->success($data, $message);
    }

    /**
     * Send error response
     */
    protected function error(string $message, int $code = 400, array $errors = []): Response
    {
        return $this->response->error($message, $code, $errors);
    }

    /**
     * Send paginated response
     */
    protected function paginated(array $items, int $total, int $page, int $perPage): Response
    {
        return $this->response->paginated($items, $total, $page, $perPage);
    }

    /**
     * Get request
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }
}
