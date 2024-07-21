<?php
namespace app\utils;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

/**
 * JWT工具类
 * @author 莹. <u@yingyya.cn>
 * @link https://www.yingyya.cn/
 * @link https://www.f4team.cn/
 */
class JWTUtils
{
    private static string $KEY = 'ba06aefedf58341f2e193bf8ac2b3bd88c710d22acac';
    private static string $ISSUED = 'f4team.cn';
    private static string $PERMITTED = 'f4pan.f4team.cn';

    private static function getJWTOb(): Configuration
    {
        return Configuration::forSymmetricSigner(new Sha256(),InMemory::base64Encoded(self::$KEY));
    }

    /**
     * 获取 Token
     * @param array $bind token 负载
     * @param bool $refreshToken 是否是刷新token
     * @return string
     */
    public static function getToken(array $bind = [], bool $refreshToken = false): string {
        $jwt = self::getJWTOb();
        $builder = $jwt->builder();
        $builder->withClaim('scope', $refreshToken ? 'refresh' : 'user-access');
        foreach ($bind as $key => $value) {
            $builder->withClaim($key, $value);
        }
        $issuedAt = new \DateTimeImmutable();
        return $builder
            ->issuedBy(self::$ISSUED)
            ->permittedFor(self::$PERMITTED)
            ->issuedAt($issuedAt)
            ->canOnlyBeUsedAfter($issuedAt->modify('-1 second')) // 提前一秒为了登录后token立即生效
            ->expiresAt($issuedAt->modify($refreshToken ? '+16 hour' : '+12 hour'))
            ->getToken($jwt->signer(), $jwt->signingKey())->toString();
    }

    /**
     * 验证并获取 Token 负载
     * @param string $t
     * @return int|array int 验证失败
     */
    public static function getPayloadAndVerify(string $t): int | array {
        $jwt = self::getJWTOb();
        $token = $jwt->parser()->parse($t);
        if ($token instanceof \UnencryptedToken) {
            // 解析失败
            return 1;
        }
        $jwt->setValidationConstraints(new IssuedBy(self::$ISSUED));
        try {
            $jwt->validator()->assert($token, ...$jwt->validationConstraints());
        } catch (RequiredConstraintsViolated) {
            // 非本服务器签发的 token
            return 2;
        }
        $timezone = new \DateTimeZone('Asia/Shanghai');
        $time = new SystemClock($timezone);
        $jwt->setValidationConstraints(new ValidAt($time));
        try {
            $jwt->validator()->assert($token, ...$jwt->validationConstraints());
        } catch (RequiredConstraintsViolated) {
            // token 过期
            return 3;
        }
        $validate_signed = new SignedWith(new Sha256(), InMemory::base64Encoded(self::$KEY));
        $jwt->setValidationConstraints($validate_signed);
        try {
            $jwt->validator()->assert($token, ...$jwt->validationConstraints());
        } catch (RequiredConstraintsViolated) {
            // 签名错误
            return 4;
        }
        // 验证成功 获取负载信息
        try {
            return json_decode(base64_decode($token->claims()->toString()), true);
        } catch (\Exception) {
            return 5; // 未知错误
        }
    }
}