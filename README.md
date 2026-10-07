# cln-arch-sym-simple

Local monorepo. Two directories at the root:

| Directory | Purpose |
|-----------|---------|
| [`api/`](api/README.md) | Main Symfony API (MySQL, JWT, business logic) |
| [`docker/`](docker/README.md) | Docker Compose for local development (nginx, PHP, database, workers) |

---

## How the project works (from the user to the data)

```mermaid
flowchart TB
  Human([Person / client])

  subgraph Edge["Entry"]
    NG8080["nginx :8080 → API"]
    CLI["CLI / Console"]
  end

  Human --> NG8080
  Human --> CLI

  subgraph API["api — Symfony Clean Architecture"]
    direction TB
    Pres["Presentation<br/>Controller / Console"]
    App["Application<br/>Model → Service::execute<br/>Action · Query · Lifecycle<br/>Policy · ViewModelMapper · Dispatcher · Cache"]
    Port["Port<br/>Repository · Storage · Mailer · Token · Cache"]
    Infra["Infrastructure<br/>Doctrine Repository · Mapper · Entity<br/>MessageHandler · Adapter · Provider"]

    Pres --> App
    App --> Port
    Port --> Infra
    App -.->|Application Event| Infra
    Infra -.->|MessageHandler → Service| App
  end

  NG8080 --> Pres
  CLI --> Pres

  subgraph InfraStore["API stores and queues"]
    MySQL[(MySQL)]
    Redis[(Redis<br/>cache / locks)]
    FS[("files<br/>local storage")]
    RMQ_async["RabbitMQ<br/>messages queue"]
  end

  Infra --> MySQL
  Infra --> Redis
  Infra --> FS
  Infra -->|Application Event| RMQ_async

  subgraph WorkersAPI["api workers"]
    MW["messenger-worker<br/>async + scheduler"]
    SideFx["*MessageHandler<br/>notifications, recalc, …"]
  end

  RMQ_async --> MW
  MW --> SideFx
  SideFx -.-> App
```

### In short

1. A **person** hits `nginx` (`:8080` — API) or runs Console.
2. **Presentation** builds a request `*Model` (`fromArray`) and calls `*Service::execute`. Console parses the CLI and calls a Lifecycle `*Service`.
3. **Application**: Policy → persistence `*Model` → Port. Action / Query return only a response `*ViewModel` | list | page | void. After a write, Lifecycle updates the cache and publishes an Application Event (`*Dispatcher`).
4. **Infrastructure** implements Port: MySQL (Doctrine), Redis, files. The Application Event goes to the RabbitMQ `messages` queue.
5. **messenger-worker** reads the queue: `*MessageHandler` calls an Application `*Service` (notifications, recalc). The same worker runs the scheduler.

Layer conventions: [`api/src/Application/CLAUDE.md`](api/src/Application/CLAUDE.md), [`api/src/Infrastructure/CLAUDE.md`](api/src/Infrastructure/CLAUDE.md).
