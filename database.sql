-- Initial database schema

CREATE TABLE tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    brand_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(255),
    currency VARCHAR(10) NOT NULL,
    watermark_text VARCHAR(255),
    price_per_stub DECIMAL(10,2),
    stripe_pk VARCHAR(255),
    stripe_sk VARCHAR(255),
    mp_keys VARCHAR(255),
    email_from VARCHAR(255)
);

CREATE TABLE orders (
    id CHAR(36) PRIMARY KEY,
    tenant_id INT,
    email VARCHAR(255) NOT NULL,
    status ENUM('draft','pending','paid') NOT NULL DEFAULT 'draft',
    template_key VARCHAR(100) NOT NULL,
    pay_schedule ENUM('weekly','biweekly','semi-monthly','monthly') NOT NULL,
    count_stubs INT NOT NULL,
    bundle_mode ENUM('combined','separate') NOT NULL DEFAULT 'separate',
    period_start DATE,
    period_end DATE,
    pay_date DATE,
    gross DECIMAL(10,2),
    net DECIMAL(10,2),
    fit_taxable_wages DECIMAL(10,2),
    taxes_total DECIMAL(10,2),
    deductions_total DECIMAL(10,2),
    employee_json JSON,
    employer_json JSON,
    pdf_path VARCHAR(255),
    version_of CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

CREATE TABLE earnings (
    order_id CHAR(36) NOT NULL,
    stub_index INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    hours DECIMAL(10,2),
    rate DECIMAL(10,2),
    current_amount DECIMAL(10,2) NOT NULL,
    ytd_amount DECIMAL(10,2) NOT NULL,
    sort_order INT DEFAULT 0,
    PRIMARY KEY (order_id, stub_index, label),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE deductions (
    order_id CHAR(36) NOT NULL,
    stub_index INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    pretax BOOLEAN DEFAULT 0,
    current_amount DECIMAL(10,2) NOT NULL,
    ytd_amount DECIMAL(10,2) NOT NULL,
    sort_order INT DEFAULT 0,
    PRIMARY KEY (order_id, stub_index, label),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE taxes (
    order_id CHAR(36) NOT NULL,
    stub_index INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    current_amount DECIMAL(10,2) NOT NULL,
    ytd_amount DECIMAL(10,2) NOT NULL,
    sort_order INT DEFAULT 0,
    PRIMARY KEY (order_id, stub_index, label),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE payments (
    order_id CHAR(36) NOT NULL,
    provider ENUM('stripe','mp') NOT NULL,
    session_id VARCHAR(255),
    status VARCHAR(50),
    currency VARCHAR(10),
    amount_total DECIMAL(10,2),
    webhook_payload_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (order_id, provider),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor ENUM('user','bot') NOT NULL,
    action ENUM('create','regenerate','resend') NOT NULL,
    order_id CHAR(36),
    meta_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
