package dev.bsolutions.printagent.dto;

import com.fasterxml.jackson.annotation.JsonProperty;

public record PrinterInfo(
        @JsonProperty("name")           String name,
        @JsonProperty("is_default")     boolean isDefault
) {}
