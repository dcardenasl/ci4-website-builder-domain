# CMS-021: Backend-for-Frontend (BFF) Architecture Decision

**Date:** 2026-06-27  
**Status:** ✅ **DECIDED — Direct Consumption**  
**Affected Components:** Domain CMS, Public Website, Admin

## Question

Should a generated public website consume the Domain CMS API directly, or should it use the
optional BFF layer that aggregates Domain and Hub APIs?

## Decision

For a site with one public consumer, direct Domain consumption is the default. The optional BFF is
introduced when multiple frontends, materially different response shapes, or meaningful aggregation
and proxy responsibilities justify the additional deployment boundary.

## Rationale

Direct consumption keeps a simple site to two runtime apps, avoids an unnecessary network hop, and
leaves presentation-specific behavior in the web app. A BFF is appropriate when the same upstream
data serves several clients or when aggregation, M2M authentication, webhook proxying, or rate
limit management forms a reusable boundary.

## Generic migration path

1. Enable the optional `ci4-website-builder-bff` app on port `8188`.
2. Point the web app at the BFF and keep Domain as the CMS source of truth.
3. Add generic proxy or composed-read routes only when the consumer contract requires them.
4. Keep site content, business modules, and tenant-specific rules in the generated site/domain
   application rather than in the starter BFF.

## Related contracts

- `bff.hubUrl` and `bff.domainUrl` identify upstreams; `BFF_ALLOWED_ORIGINS` is explicit.
- The BFF forwards bearer authentication; it does not mint JWTs or own user storage.
- `PublicReadSupport` is read-only, opt-in, and disabled by default.
- The public web app retains a direct Domain fallback for deployments without the BFF.
