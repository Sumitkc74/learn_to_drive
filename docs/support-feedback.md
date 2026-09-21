> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Support and feedback

Admin inbox: `/admin/support`, linked as **Support & Feedback** in the sidebar.
Search subject/message and filter by type or status. Open a ticket to read its
conversation, reply, or set New / In Progress / Resolved. Replies are user-visible.

The learner app must connect its support screen to these endpoints using its
Sanctum bearer token and `Accept: application/json`:

- `POST /api/support-tickets`: create; returns 201 and `{data: ticket}`.
- `GET /api/support-tickets`: paginated own tickets, 20 per page.
- `GET /api/support-tickets/{id}`: own ticket and replies under `data`.
- `POST /api/support-tickets/{id}/replies`: `{ "message": "..." }`; reopens as New.

Example submission:

```json
{
  "type": "Content report",
  "subject": "Please check this question",
  "message": "The answer seems incorrect.",
  "content_type": "question",
  "content_id": 123
}
```

Types: Complaint, Feature request, Content report, Other.
Related content is optional; provide both fields when used. Supported types:
question, traffic-sign, vision-test. The referenced record must exist.
Subject limit: 200 characters. Message/reply limit: 5,000 characters.
Creation: 5 requests per 10 minutes; replies: 10 per 10 minutes.
Validation returns 422, unauthenticated requests 401, inaccessible ticket IDs 404,
and rate limits 429. User identity and initial status are assigned by the server.

No email or push notification is sent. The learner app reads replies through the
API. This repository change supplies the backend and admin interface; the learner
app's submission and conversation screens still need to be connected.
