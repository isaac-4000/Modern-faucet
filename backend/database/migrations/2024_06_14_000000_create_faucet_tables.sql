-- Esquema do Banco de Dados para o Site de Faucet de Criptomoeda Moderno

-- Tabela de Usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_address VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(18, 8) DEFAULT 0.00000000,
    referral_code VARCHAR(50) UNIQUE,
    referred_by VARCHAR(255),
    last_claim_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Administradores
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Transações (para faucet claims, ganhos de jogo, etc.)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM("faucet_claim", "dice_win", "dice_loss", "referral_bonus", "withdrawal") NOT NULL,
    amount DECIMAL(18, 8) NOT NULL,
    currency VARCHAR(10) DEFAULT "BTC",
    transaction_hash VARCHAR(255) UNIQUE,
    status ENUM("pending", "completed", "failed") DEFAULT "pending",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Jogos de Dados
CREATE TABLE dice_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bet_amount DECIMAL(18, 8) NOT NULL,
    roll_result DECIMAL(5, 2) NOT NULL, -- Resultado do dado (ex: 0.00 a 99.99)
    target_number DECIMAL(5, 2) NOT NULL, -- Número alvo para ganhar
    is_win BOOLEAN NOT NULL,
    payout_amount DECIMAL(18, 8) NOT NULL,
    server_seed VARCHAR(255) NOT NULL,
    client_seed VARCHAR(255) NOT NULL,
    nonce INT NOT NULL,
    hashed_server_seed VARCHAR(255) NOT NULL, -- Hash do server_seed antes do jogo
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Payouts (registra todos os saques)
CREATE TABLE payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    amount DECIMAL(18, 8) NOT NULL,
    currency VARCHAR(10) DEFAULT "BTC",
    transaction_id VARCHAR(255) UNIQUE, -- ID da transação na API de pagamento
    status ENUM("pending", "completed", "failed") DEFAULT "pending",
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Configurações (para parâmetros da faucet, como tempo de espera, valores de claim)
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir algumas configurações iniciais (exemplo)
INSERT INTO settings (setting_key, setting_value, description) VALUES
('faucet_claim_amount', '0.00000010', 'Quantidade de BTC por claim da faucet'),
('faucet_claim_interval_seconds', '1800', 'Intervalo em segundos entre claims da faucet (30 minutos)'),
('referral_percentage', '0.10', 'Porcentagem de comissão por referência'),
('min_withdrawal_amount', '0.00001000', 'Valor mínimo para saque');


