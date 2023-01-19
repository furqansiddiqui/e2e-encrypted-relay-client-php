# End-to-end encrypted HTTP relay client for PHP apps

This lib is to be used
with :package: [e2e-encrypted-relay-server](https://github.com/furqansiddiqui/e2e-encrypted-relay-server) nodes.
Check the link for more information.

## Specification

### HTTP Response Codes

| Code | Description                                                                                                                                                                                                                                                                                                 |
|------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 403  | IP address is not whitelisted                                                                                                                                                                                                                                                                               |
| 204  | This status code is returned when a request is made to server with HTTP method `GET`. No content/body is returned. Making it effectively a quick "ping" type call to check if E2E relay node is up and running.                                                                                             |
| 451  | Encrypted body could not be decrypted as instance of `RelayCurlRequest` object. This indicates a possible error in encrypted bytes received OR wrong shared secret string. Returning body may contain more information about the actual error.                                                              |
| 202  | This status code is sent without any body when a instance of `RelayCurlRequest` object is successfully decrypted but has its constructor argument "method" set to `handshake`. This effectively means the shared secret between client and relay node verifies and no further action is required/requested. |
| 452  | There was an error while creating Curl request from decrypted `RelayCurlRequest` object. Content body is comprised of "[error-code]\t[error-message]" (separated by `tab` character)                                                                                                                        |
| 453  | There was an error while sending Curl request from decrypted `RelayCurlRequest` object. This normally contains error code and message received directly from Curl lib. Content body is comprised of "[error-code]\t[error-message]" (separated by `tab` character)                                          |
| 454  | An error occurred while encrypting `RelayCurlResponse` instance.                                                                                                                                                                                                                                            |
| 250  | Content body contains encrypted instance of `RelayCurlResponse` object that has been created as result of received  `RelayCurlRequest` object.                                                                                                                                                              |


