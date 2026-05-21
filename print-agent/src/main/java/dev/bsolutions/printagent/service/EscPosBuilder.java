package dev.bsolutions.printagent.service;

import org.springframework.stereotype.Component;

import java.io.ByteArrayOutputStream;
import java.nio.charset.Charset;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Builds raw ESC/POS byte sequences for thermal printers.
 * Not thread-safe — create a new instance per print job via reset().
 *
 * Supports an inline marker `[[QR:<data>]]` inside the text content that gets
 * replaced by ESC/POS QR commands (model 2). Useful for ticket URLs that the
 * customer scans with any phone.
 */
@Component
public class EscPosBuilder {

    // ESC/POS command constants
    private static final byte[] CMD_INIT      = {0x1B, 0x40};           // Initialize printer
    private static final byte[] CMD_ALIGN_L   = {0x1B, 0x61, 0x00};     // Align left
    private static final byte[] CMD_ALIGN_C   = {0x1B, 0x61, 0x01};     // Align center
    private static final byte[] CMD_BOLD_ON   = {0x1B, 0x45, 0x01};     // Bold on
    private static final byte[] CMD_BOLD_OFF  = {0x1B, 0x45, 0x00};     // Bold off
    private static final byte[] CMD_SIZE_NORM = {0x1D, 0x21, 0x00};     // Normal size
    private static final byte[] CMD_CUT_FULL  = {0x1D, 0x56, 0x41, 0x05}; // Full cut with feed

    private static final Pattern QR_MARKER = Pattern.compile("\\[\\[QR:(.+?)]]");

    private final List<byte[]> buffer = new ArrayList<>();

    public EscPosBuilder reset() {
        buffer.clear();
        buffer.add(CMD_INIT);
        buffer.add(CMD_ALIGN_L);
        buffer.add(CMD_SIZE_NORM);
        return this;
    }

    /**
     * Append text content (already formatted), encoding each line with the given charset.
     * Any line that matches `[[QR:<data>]]` is replaced by ESC/POS QR commands
     * (centered, module size 6, error correction M).
     */
    public EscPosBuilder text(String content, String charsetName) {
        Charset cs = resolveCharset(charsetName);
        for (String line : content.split("\n", -1)) {
            Matcher m = QR_MARKER.matcher(line.trim());
            if (m.matches()) {
                String qrData = m.group(1);
                buffer.add(CMD_ALIGN_C);
                appendQrCode(qrData);
                buffer.add(CMD_ALIGN_L);
            } else {
                buffer.add((line + "\r\n").getBytes(cs));
            }
        }
        return this;
    }

    /** Append blank feed lines. */
    public EscPosBuilder feed(int lines) {
        byte[] lf = new byte[lines];
        for (int i = 0; i < lines; i++) lf[i] = 0x0A;
        buffer.add(lf);
        return this;
    }

    /** Append a full paper cut command. */
    public EscPosBuilder cut() {
        buffer.add(CMD_CUT_FULL);
        return this;
    }

    /**
     * Append the full ESC/POS sequence to print a QR code (model 2).
     * Module size 6 (~21mm at 203dpi), error correction M.
     * Followed by a line feed.
     */
    public EscPosBuilder qrCode(String data) {
        appendQrCode(data);
        return this;
    }

    private void appendQrCode(String data) {
        if (data == null || data.isEmpty()) return;

        // 1) Select QR model 2: GS ( k pL pH cn fn n1 n2 -> cn=49 fn=65 n1=50 n2=0
        buffer.add(new byte[]{0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00});

        // 2) Module size: GS ( k pL pH cn fn n  -> cn=49 fn=67 n=6 (1..16)
        buffer.add(new byte[]{0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, 0x06});

        // 3) Error correction level M: GS ( k pL pH cn fn n -> cn=49 fn=69 n=49 (L=48 M=49 Q=50 H=51)
        buffer.add(new byte[]{0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x31});

        // 4) Store data: GS ( k pL pH cn fn m <data>  -> cn=49 fn=80 m=48
        byte[] payload = data.getBytes(Charset.forName("UTF-8"));
        int len = payload.length + 3;
        int pL = len & 0xFF;
        int pH = (len >> 8) & 0xFF;
        ByteArrayOutputStream store = new ByteArrayOutputStream();
        store.write(0x1D); store.write(0x28); store.write(0x6B);
        store.write(pL); store.write(pH);
        store.write(0x31); store.write(0x50); store.write(0x30);
        store.write(payload, 0, payload.length);
        buffer.add(store.toByteArray());

        // 5) Print: GS ( k pL pH cn fn m -> cn=49 fn=81 m=48
        buffer.add(new byte[]{0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30});

        // Line feed after QR so it doesn't collide with the next line
        buffer.add(new byte[]{0x0A});
    }

    public byte[] build() {
        int total = buffer.stream().mapToInt(b -> b.length).sum();
        byte[] result = new byte[total];
        int pos = 0;
        for (byte[] chunk : buffer) {
            System.arraycopy(chunk, 0, result, pos, chunk.length);
            pos += chunk.length;
        }
        return result;
    }

    private Charset resolveCharset(String name) {
        try {
            return Charset.forName(name);
        } catch (Exception e) {
            return Charset.forName("ISO-8859-1");
        }
    }
}
