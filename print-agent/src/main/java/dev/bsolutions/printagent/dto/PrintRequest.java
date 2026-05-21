package dev.bsolutions.printagent.dto;

import com.fasterxml.jackson.annotation.JsonProperty;

public record PrintRequest(
        @JsonProperty("job_uuid")       String jobUuid,
        @JsonProperty("printer_name")   String printerName,
        @JsonProperty("connection_type") String connectionType,
        @JsonProperty("content")        String content,
        @JsonProperty("paper_width")    String paperWidth
) {}
