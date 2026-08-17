FROM rabbitmq:3.13-management

RUN apt-get update && apt-get install -y wget && rm -rf /var/lib/apt/lists/*

RUN wget https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v3.13.0/rabbitmq_delayed_message_exchange-3.13.0.ez -O /plugins/rabbitmq_delayed_message_exchange-3.13.0.ez

RUN rabbitmq-plugins enable rabbitmq_delayed_message_exchange
