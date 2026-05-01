#### Responses

**Success (200)**:

```json
{
  "status": "success",
  "data": {
    {{response_structure}}
  }
}
```

**Error (4xx/5xx)**:

```json
{
  "status": "error",
  "error": {
    "code": "{{error_code}}",
    "message": "{{error_message}}"
  }
}
```
