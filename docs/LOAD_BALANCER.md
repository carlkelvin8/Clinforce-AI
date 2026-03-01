# Load Balancer Architecture and Operations

## Overview
This document describes the load balancer setup for clinforce-ai to prevent crashes during traffic spikes and to enable zero‑downtime deployments.

## Components
- HAProxy (VM/Container): L4/L7 load balancer with active health checks, least-connections, and optional sticky sessions.
- Kubernetes (optional, recommended): NGINX Ingress for L7 balancing, HPA for autoscaling, rolling updates for zero‑downtime, and Prometheus alerts.
- Health endpoint: `GET /api/health` returns JSON with app/db health used by LB and k8s probes.

## HAProxy (VM/Container)
Config path: `deploy/haproxy/haproxy.cfg`
- Balancing: `leastconn`
- Health checks: `option httpchk GET /api/health` expects 200
- Stickiness: cookie `SRVID` (comment out to disable)
- Stats: `:8404/stats`

Start with Docker Compose (replace image with your published build):
```bash
docker compose -f deploy/docker-compose.lb.yml up -d
```

## Kubernetes Manifests
Location: `deploy/k8s/`
- `deployment.yaml`: 3 replicas, readiness/liveness probes, rolling update (maxSurge 1, maxUnavailable 0)
- `service.yaml`: ClusterIP service on port 80
- `ingress.yaml`: NGINX Ingress, optional sticky sessions via cookie
- `hpa.yaml`: Autoscaling 3..20 replicas, CPU 70% and memory 80%
- `prometheus-rules.yaml`: Alerts for 5xx rate, high latency, and unavailable replicas

Apply:
```bash
kubectl apply -f deploy/k8s/
```

## Algorithms
- Use `leastconn` for bursty, long‑running requests; use `roundrobin` for uniformly short requests.
- Weight instances by capacity using `weight` in HAProxy or multiple replicas for stronger nodes in k8s.

## Session Persistence
- The API uses token auth and is stateless; sticky sessions are not required by default.
- If needed (e.g., WebSocket fallbacks), enable sticky cookie in Ingress or HAProxy’s `cookie SRVID`.

## Health Checks
- HAProxy: active HTTP checks against `/api/health`
- Kubernetes: `readinessProbe` and `livenessProbe` on `/api/health`
- Ensure DB is reachable; endpoint returns `degraded` if DB fails, preventing new traffic until recovered.

## Auto‑Scaling
- Kubernetes HPA scales based on CPU and memory utilization.
- For cloud LBs (AWS ALB), use Target Tracking Scaling Policies (CPU 70%) on the underlying compute (EKS/ECS/EC2).

## Monitoring & Alerting
- If using NGINX Ingress: scrape `nginx_ingress_controller` metrics with Prometheus; apply `prometheus-rules.yaml`.
- For AWS ALB: configure CloudWatch alarms on 5xx count, TargetResponseTime p95, UnhealthyHostCount.
- Add on‑call routing via your alert platform (PagerDuty, OpsGenie).

## Zero‑Downtime Deployments
- Kubernetes rolling updates with `maxUnavailable: 0` prevent traffic loss.
- Ensure `readinessProbe` passes only when the app is accepting traffic.
- For HAProxy on VMs, reload using seamless reload (`-sf` with pidfile) after updating backend pool.

## Load Testing
- Script: `perf/k6-load-test.js`
- Run:
```bash
k6 run -e BASE_URL=https://your-domain k6-load-test.js
```
- Target: 10x baseline traffic with p95 < 500ms and <1% error rate. Tune replica counts and instance sizes as needed.

## Troubleshooting
- Many 5xx: Check app logs and DB connectivity; confirm `/api/health` returns 200.
- High latency: Scale up via HPA; check DB queries and external dependencies.
- Sticky issues: Disable stickiness; ensure token auth; inspect cache/session backends if used.
- LB health failures: Curl `/api/health` from within LB network; verify firewall and DNS.

## Configuration Parameters
- HAProxy: `balance`, `inter/fall/rise`, `maxconn`, stickiness, server weights.
- K8S: `resources.requests/limits`, HPA thresholds, replica counts, Ingress affinity.

## Notes
- Do not store secrets in manifests. Use Kubernetes Secrets or external secret managers.
- Review compliance and logging requirements before enabling request logs in production LBs.
