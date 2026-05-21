package dev.bsolutions.printagent.config;

import org.springframework.boot.context.properties.ConfigurationProperties;

@ConfigurationProperties(prefix = "agent")
public record AgentProperties(
        String token,
        String charset,
        boolean logJobs
) {
    public AgentProperties {
        if (token == null || token.isBlank()) {
            throw new IllegalStateException("agent.token must be set in application.yml");
        }
        if (charset == null || charset.isBlank()) {
            charset = "CP850";
        }
    }
}
