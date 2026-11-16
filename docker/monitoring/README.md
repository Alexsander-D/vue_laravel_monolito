# Monitoramento da EC2 e dos containers

Defina uma senha forte no `.env` da EC2:

```env
GRAFANA_ADMIN_PASSWORD=uma-senha-forte
```

Inicie o stack:

```bash
docker compose up -d prometheus node-exporter cadvisor grafana
```

O painel externo do Grafana fica em `127.0.0.1:3000`. Para acessá-lo com segurança:

```bash
ssh -L 3000:127.0.0.1:3000 usuario@ip-da-ec2
```

Depois acesse `http://localhost:3000`. O Laravel usa o Prometheus internamente pela variável `PROMETHEUS_URL` e exibe o resumo na tela `Monitoramento do sistema`.

O cAdvisor mede memória e CPU por container, incluindo o banco quando ele estiver no mesmo Docker host. Métricas internas do banco, como conexões e queries, exigem o exporter específico do banco utilizado.
