##### 5.9.7.4 Behavior

- The OpenAI API receives requests containing prompts, model parameters, and the API key.
- Processing in the OpenAI cloud: models process the input and generate a response.
- The API response includes the generated text/image, token usage information, and model details.
- The behavior can be synchronous or asynchronous (streaming responses) depending on the endpoint and configuration.
- Implement input validation and sanitization to prevent prompt injections or abuse.
